<?php

namespace App\Services\Tripay;

use App\Exceptions\TripayException;
use App\Models\ActivityLog;
use App\Models\Transaction;
use App\Models\TripayPayment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TripayPaymentService
{
    public function __construct(private readonly TripayClient $client) {}

    public function create(Transaction $transaction, string $method, string $customerEmail): TripayPayment
    {
        $this->client->ensureConfigured();
        $transaction->loadMissing(['customer', 'items']);

        if ($transaction->payment_status === 'lunas') {
            throw ValidationException::withMessages([
                'tripay' => 'Transaksi ini sudah lunas.',
            ]);
        }

        if ($transaction->tripayPayments()->get()->contains->isPending()) {
            throw ValidationException::withMessages([
                'tripay' => 'Masih ada pembayaran online yang aktif untuk transaksi ini.',
            ]);
        }

        $amount = $this->rupiahAmount($transaction);
        $channel = collect($this->client->paymentChannels())->firstWhere('code', $method);

        if (! is_array($channel)) {
            throw ValidationException::withMessages([
                'method' => 'Channel pembayaran online tidak tersedia atau tidak aktif.',
            ]);
        }

        $minimum = (int) ($channel['minimum_amount'] ?? 0);
        $maximum = (int) ($channel['maximum_amount'] ?? PHP_INT_MAX);

        if ($amount < $minimum || $amount > $maximum) {
            throw ValidationException::withMessages([
                'method' => 'Total transaksi di luar batas nominal channel yang dipilih.',
            ]);
        }

        $payment = DB::transaction(function () use ($transaction, $method, $customerEmail, $amount): TripayPayment {
            $lockedTransaction = Transaction::query()->lockForUpdate()->findOrFail($transaction->id);

            if ($lockedTransaction->payment_status === 'lunas') {
                throw ValidationException::withMessages([
                    'tripay' => 'Transaksi ini sudah lunas.',
                ]);
            }

            $hasPendingPayment = $lockedTransaction->tripayPayments()
                ->whereIn('status', ['CREATING', 'UNPAID'])
                ->exists();

            if ($hasPendingPayment) {
                throw ValidationException::withMessages([
                    'tripay' => 'Masih ada pembayaran online yang aktif untuk transaksi ini.',
                ]);
            }

            $lockedTransaction->customer?->update(['email' => $customerEmail]);

            return $lockedTransaction->tripayPayments()->create([
                'merchant_ref' => $lockedTransaction->invoice_code.'-'.Str::upper(Str::random(8)),
                'payment_method' => $method,
                'customer_email' => $customerEmail,
                'status' => 'CREATING',
                'amount' => $amount,
            ]);
        });

        $expiryMinutes = max(10, min((int) config('services.tripay.expiry_minutes', 60), 4320));
        $expiredAt = now()->addMinutes($expiryMinutes);

        $payload = [
            'method' => $method,
            'merchant_ref' => $payment->merchant_ref,
            'amount' => $amount,
            'customer_name' => (string) $transaction->customer?->name,
            'customer_email' => $customerEmail,
            'customer_phone' => (string) $transaction->customer?->phone,
            'order_items' => $this->orderItems($transaction, $amount),
            'callback_url' => config('services.tripay.callback_url') ?: route('tripay.callback'),
            'return_url' => URL::temporarySignedRoute(
                'tripay.return',
                $expiredAt->copy()->addHour(),
                ['tripayPayment' => $payment->merchant_ref]
            ),
            'expired_time' => $expiredAt->timestamp,
            'signature' => $this->client->transactionSignature($payment->merchant_ref, $amount),
        ];

        try {
            $providerData = $this->client->createTransaction($payload);
            $payment = $this->updateFromProvider($payment, $providerData);
        } catch (TripayException $exception) {
            $payment->update([
                'status' => 'ERROR',
                'failure_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        ActivityLog::record(
            'payment',
            "Buat pembayaran online {$payment->merchant_ref} ({$payment->payment_method})",
            $payment
        );

        return $payment;
    }

    public function sync(TripayPayment $payment): TripayPayment
    {
        if (! $payment->reference) {
            throw new TripayException('Referensi pembayaran belum tersedia untuk disinkronkan.');
        }

        return $this->updateFromProvider(
            $payment,
            $this->client->transactionDetail($payment->reference)
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function handleCallback(array $data): TripayPayment
    {
        return DB::transaction(function () use ($data): TripayPayment {
            $payment = TripayPayment::query()
                ->where('merchant_ref', $data['merchant_ref'])
                ->lockForUpdate()
                ->first();

            if (! $payment || ($payment->reference && $payment->reference !== $data['reference'])) {
                throw new TripayException('Pembayaran online tidak ditemukan.');
            }

            $callbackAmount = (int) $data['total_amount'];
            $customerFee = $payment->fee_customer ?? (int) $data['fee_customer'];

            if (! in_array($callbackAmount, [$payment->amount, $payment->amount + $customerFee], true)) {
                throw new TripayException('Nominal callback pembayaran tidak sesuai dengan transaksi.');
            }

            $incomingStatus = strtoupper((string) $data['status']);
            $currentStatus = $payment->status;

            if ($this->shouldIgnoreTransition($currentStatus, $incomingStatus)) {
                $payment->update(['last_callback' => $data]);

                return $payment->fresh();
            }

            $payment->update([
                'reference' => $data['reference'],
                'payment_method' => $data['payment_method_code'],
                'payment_name' => $data['payment_method'],
                'status' => $incomingStatus,
                'fee_merchant' => $data['fee_merchant'],
                'fee_customer' => $data['fee_customer'],
                'total_fee' => $data['total_fee'],
                'amount_received' => $data['amount_received'],
                'paid_at' => $this->timestamp($data['paid_at'] ?? null),
                'last_callback' => $data,
                'failure_message' => null,
            ]);

            $payment = $payment->fresh();
            $this->applyPaymentStatus($payment);

            if ($currentStatus !== $incomingStatus) {
                ActivityLog::record(
                    'payment_callback',
                    "Callback pembayaran online {$payment->merchant_ref}: {$incomingStatus}",
                    $payment,
                    null
                );
            }

            return $payment;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function updateFromProvider(TripayPayment $payment, array $data): TripayPayment
    {
        return DB::transaction(function () use ($payment, $data): TripayPayment {
            $payment = TripayPayment::query()->lockForUpdate()->findOrFail($payment->id);

            if (($data['merchant_ref'] ?? null) !== $payment->merchant_ref
                || (int) ($data['amount'] ?? -1) !== $payment->amount
                || blank($data['reference'] ?? null)) {
                throw new TripayException('Data dari penyedia pembayaran tidak cocok dengan pembayaran lokal.');
            }

            $status = strtoupper((string) ($data['status'] ?? 'UNPAID'));

            if (! in_array($status, ['UNPAID', 'PAID', 'FAILED', 'EXPIRED', 'REFUND'], true)) {
                throw new TripayException('Status dari penyedia pembayaran tidak dikenali.');
            }

            if ($this->shouldIgnoreTransition($payment->status, $status)) {
                return $payment;
            }

            $payment->update([
                'reference' => $data['reference'],
                'payment_method' => $data['payment_method'] ?? $payment->payment_method,
                'payment_name' => $data['payment_name'] ?? $payment->payment_name,
                'status' => $status,
                'amount' => (int) $data['amount'],
                'fee_merchant' => $this->nullableInt($data['fee_merchant'] ?? null),
                'fee_customer' => $this->nullableInt($data['fee_customer'] ?? null),
                'total_fee' => $this->nullableInt($data['total_fee'] ?? null),
                'amount_received' => $this->nullableInt($data['amount_received'] ?? null),
                'pay_code' => isset($data['pay_code']) ? (string) $data['pay_code'] : null,
                'pay_url' => $data['pay_url'] ?? null,
                'checkout_url' => $data['checkout_url'] ?? null,
                'qr_string' => $data['qr_string'] ?? null,
                'qr_url' => $data['qr_url'] ?? null,
                'instructions' => is_array($data['instructions'] ?? null) ? $data['instructions'] : null,
                'provider_data' => $data,
                'failure_message' => null,
                'expired_at' => $this->timestamp($data['expired_time'] ?? null),
                'paid_at' => $this->timestamp($data['paid_at'] ?? null),
            ]);

            $payment = $payment->fresh();
            $this->applyPaymentStatus($payment);

            return $payment;
        });
    }

    private function applyPaymentStatus(TripayPayment $payment): void
    {
        $transaction = Transaction::query()->lockForUpdate()->findOrFail($payment->transaction_id);

        if ($payment->status === 'PAID') {
            if ($transaction->payment_status === 'lunas'
                && $transaction->payment_method !== null
                && $transaction->payment_method !== 'tripay') {
                return;
            }

            $transaction->update([
                'payment_status' => 'lunas',
                'payment_method' => 'tripay',
                'paid_at' => $payment->paid_at ?? now(),
            ]);

            return;
        }

        $hasAnotherPaidPayment = $transaction->tripayPayments()
            ->where('id', '!=', $payment->id)
            ->where('status', 'PAID')
            ->exists();

        if ($payment->status === 'REFUND'
            && $transaction->payment_method === 'tripay'
            && ! $hasAnotherPaidPayment) {
            $transaction->update([
                'payment_status' => 'belum_lunas',
                'payment_method' => null,
                'paid_at' => null,
            ]);
        }
    }

    private function rupiahAmount(Transaction $transaction): int
    {
        $total = (float) $transaction->total;
        $rounded = (int) round($total);

        if ($rounded <= 0 || abs($total - $rounded) > 0.001) {
            throw ValidationException::withMessages([
                'tripay' => 'Total pembayaran online harus berupa Rupiah bulat dan lebih dari nol.',
            ]);
        }

        return $rounded;
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    private function orderItems(Transaction $transaction, int $amount): array
    {
        $items = [];
        $remaining = $amount;
        $lastIndex = $transaction->items->count() - 1;

        foreach ($transaction->items->values() as $index => $item) {
            $price = $index === $lastIndex ? $remaining : (int) round((float) $item->subtotal);
            $remaining -= $price;
            $name = $item->service_name ?? $item->service?->name ?? 'Layanan Laundry';
            $unit = $item->service_unit ?? $item->service?->unit ?? 'unit';

            $items[] = [
                'sku' => 'ITEM-'.$item->id,
                'name' => Str::limit("{$name} ({$item->quantity} {$unit})", 150, ''),
                'price' => $price,
                'quantity' => 1,
            ];
        }

        return $items;
    }

    private function timestamp(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::createFromTimestamp((int) $value, config('app.timezone'));
    }

    private function nullableInt(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }

    private function shouldIgnoreTransition(string $currentStatus, string $incomingStatus): bool
    {
        if ($currentStatus === 'REFUND') {
            return $incomingStatus !== 'REFUND';
        }

        if ($currentStatus === 'PAID') {
            return ! in_array($incomingStatus, ['PAID', 'REFUND'], true);
        }

        return in_array($currentStatus, ['FAILED', 'EXPIRED'], true)
            && $incomingStatus === 'UNPAID';
    }
}
