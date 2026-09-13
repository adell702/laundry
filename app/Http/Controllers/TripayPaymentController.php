<?php

namespace App\Http\Controllers;

use App\Exceptions\TripayException;
use App\Models\ActivityLog;
use App\Models\Transaction;
use App\Models\TripayPayment;
use App\Services\Tripay\TripayClient;
use App\Services\Tripay\TripayPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TripayPaymentController extends Controller
{
    public function create(
        Transaction $transaction,
        TripayClient $client
    ): View|RedirectResponse {
        $transaction->load(['customer', 'latestTripayPayment']);

        if ($transaction->payment_status === 'lunas') {
            return redirect()->route('transactions.show', $transaction)
                ->withErrors(['tripay' => 'Transaksi ini sudah lunas.']);
        }

        if ($transaction->latestTripayPayment?->isPending()) {
            return redirect()->route('tripay-payments.show', [
                $transaction,
                $transaction->latestTripayPayment,
            ]);
        }

        try {
            $channels = collect($client->paymentChannels())
                ->sortBy(fn (array $channel): string => ($channel['group'] ?? '').'|'.($channel['name'] ?? ''))
                ->groupBy('group');
        } catch (TripayException $exception) {
            return redirect()->route('transactions.show', $transaction)
                ->withErrors(['tripay' => $exception->getMessage()]);
        }

        return view('tripay-payments.create', compact('transaction', 'channels'));
    }

    public function store(
        Request $request,
        Transaction $transaction,
        TripayPaymentService $payments
    ): RedirectResponse {
        $data = $request->validate([
            'method' => ['required', 'string', 'max:50'],
            'customer_email' => ['required', 'email:rfc', 'max:255'],
        ]);

        try {
            $payment = $payments->create($transaction, $data['method'], $data['customer_email']);
        } catch (TripayException $exception) {
            return back()->withInput()->withErrors(['tripay' => $exception->getMessage()]);
        }

        return redirect()->route('tripay-payments.show', [$transaction, $payment])
            ->with('success', 'Pembayaran online berhasil dibuat.');
    }

    public function show(Transaction $transaction, TripayPayment $tripayPayment): View
    {
        $this->ensurePaymentBelongsToTransaction($transaction, $tripayPayment);
        $tripayPayment->load('transaction.customer');

        return view('tripay-payments.show', [
            'payment' => $tripayPayment,
            'transaction' => $transaction,
            'checkoutUrl' => $tripayPayment->safeCheckoutUrl(),
            'qrUrl' => $tripayPayment->safeQrUrl(),
        ]);
    }

    public function sync(
        Transaction $transaction,
        TripayPayment $tripayPayment,
        TripayPaymentService $payments
    ): RedirectResponse {
        $this->ensurePaymentBelongsToTransaction($transaction, $tripayPayment);

        try {
            $payment = $payments->sync($tripayPayment);
        } catch (TripayException $exception) {
            return back()->withErrors(['tripay' => $exception->getMessage()]);
        }

        ActivityLog::record(
            'payment_sync',
            "Sinkronisasi pembayaran online {$payment->merchant_ref}: {$payment->status}",
            $payment
        );

        return back()->with('success', 'Status pembayaran online berhasil disinkronkan.');
    }

    public function checkout(Transaction $transaction, TripayPayment $tripayPayment): RedirectResponse
    {
        $this->ensurePaymentBelongsToTransaction($transaction, $tripayPayment);

        $url = $tripayPayment->safeCheckoutUrl();

        if (! $tripayPayment->isPayable() || ! $url) {
            return back()->withErrors(['tripay' => 'Checkout online tidak tersedia untuk pembayaran ini.']);
        }

        return redirect()->away($url);
    }

    public function handleReturn(
        TripayPayment $tripayPayment,
        TripayPaymentService $payments
    ): View {
        $syncError = null;

        try {
            $tripayPayment = $payments->sync($tripayPayment);
        } catch (TripayException $exception) {
            $syncError = $exception->getMessage();
        }

        $tripayPayment->load('transaction.customer');

        return view('tripay-payments.return', [
            'payment' => $tripayPayment,
            'transaction' => $tripayPayment->transaction,
            'syncError' => $syncError,
            'checkoutUrl' => $tripayPayment->safeCheckoutUrl(),
            'qrUrl' => $tripayPayment->safeQrUrl(),
        ]);
    }

    private function ensurePaymentBelongsToTransaction(
        Transaction $transaction,
        TripayPayment $payment
    ): void {
        abort_unless($payment->transaction_id === $transaction->id, 404);
    }
}
