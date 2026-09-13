<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\TripayPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class TripayPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config([
            'services.tripay.enabled' => true,
            'services.tripay.mode' => 'sandbox',
            'services.tripay.api_key' => 'sandbox-api-key',
            'services.tripay.private_key' => 'sandbox-private-key',
            'services.tripay.merchant_code' => 'T1234',
            'services.tripay.callback_url' => 'https://merchant.example/api/payments/callback',
            'services.tripay.expiry_minutes' => 60,
        ]);
    }

    public function test_cashier_can_create_a_signed_sandbox_payment(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $transaction = $this->createTransaction($cashier);

        Http::fake([
            'https://tripay.co.id/api-sandbox/merchant/payment-channel' => Http::response([
                'success' => true,
                'data' => [$this->channel()],
            ]),
            'https://tripay.co.id/api-sandbox/transaction/create' => function (ClientRequest $request) {
                $merchantRef = (string) $request['merchant_ref'];
                $expectedSignature = hash_hmac(
                    'sha256',
                    'T1234'.$merchantRef.'10000',
                    'sandbox-private-key'
                );

                $this->assertSame('Bearer sandbox-api-key', $request->header('Authorization')[0]);
                $this->assertSame($expectedSignature, $request['signature']);
                $this->assertSame('https://merchant.example/api/payments/callback', $request['callback_url']);
                $this->assertSame(10_000, (int) $request['amount']);
                $this->assertSame(1, (int) $request['order_items'][0]['quantity']);

                return Http::response([
                    'success' => true,
                    'data' => $this->providerData($merchantRef),
                ]);
            },
        ]);

        $response = $this->actingAs($cashier)->post(
            route('tripay-payments.store', $transaction),
            ['method' => 'BRIVA', 'customer_email' => 'pelanggan@example.com']
        );

        $payment = TripayPayment::firstOrFail();
        $response->assertRedirect(route('tripay-payments.show', [$transaction, $payment]));
        $this->assertSame('UNPAID', $payment->status);
        $this->assertSame('TRIPAY-REFERENCE-1', $payment->reference);
        $this->assertSame('pelanggan@example.com', $transaction->customer->fresh()->email);
        $this->assertSame('belum_lunas', $transaction->fresh()->payment_status);
    }

    public function test_cashier_can_open_channel_picker_and_payment_detail_pages(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $transaction = $this->createTransaction($cashier);

        Http::fake([
            'https://tripay.co.id/api-sandbox/merchant/payment-channel' => Http::response([
                'success' => true,
                'data' => [$this->channel()],
            ]),
        ]);

        $this->actingAs($cashier)
            ->get(route('tripay-payments.create', $transaction))
            ->assertOk()
            ->assertSee('BRI Virtual Account');

        $payment = $this->createPayment($transaction, [
            'instructions' => [[
                'title' => 'Mobile Banking',
                'steps' => ['Masukkan kode bayar'],
            ]],
        ]);

        $this->actingAs($cashier)
            ->get(route('tripay-payments.show', [$transaction, $payment]))
            ->assertOk()
            ->assertSee('Masukkan kode bayar')
            ->assertSee('1234567890');
    }

    public function test_an_active_tripay_payment_prevents_duplicate_checkout_and_manual_payment(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $transaction = $this->createTransaction($cashier);
        $payment = $this->createPayment($transaction, ['status' => 'UNPAID']);

        $this->actingAs($cashier)
            ->post(route('tripay-payments.store', $transaction), [
                'method' => 'BRIVA',
                'customer_email' => 'pelanggan@example.com',
            ])
            ->assertSessionHasErrors('tripay');

        $this->actingAs($cashier)
            ->patch(route('transactions.status', $transaction), [
                'payment_status' => 'lunas',
                'payment_method' => 'tunai',
            ])
            ->assertSessionHasErrors('tripay');

        $this->assertSame('belum_lunas', $transaction->fresh()->payment_status);
        $this->assertDatabaseCount('tripay_payments', 1);
        $this->assertTrue($payment->fresh()->isPending());
        Http::assertNothingSent();
    }

    public function test_callback_rejects_an_invalid_signature(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $transaction = $this->createTransaction($cashier);
        $payment = $this->createPayment($transaction);
        $json = json_encode($this->callbackPayload($payment), JSON_THROW_ON_ERROR);

        $this->postCallback($json, 'invalid-signature')
            ->assertUnauthorized()
            ->assertJson(['success' => false]);

        $this->assertSame('UNPAID', $payment->fresh()->status);
        $this->assertSame('belum_lunas', $transaction->fresh()->payment_status);
    }

    public function test_paid_callback_is_verified_and_idempotently_marks_transaction_paid(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $transaction = $this->createTransaction($cashier);
        $payment = $this->createPayment($transaction);
        $payload = $this->callbackPayload($payment);
        $json = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $json, 'sandbox-private-key');

        $this->postCallback($json, $signature)
            ->assertOk()
            ->assertExactJson(['success' => true]);
        $this->postCallback($json, $signature)
            ->assertOk()
            ->assertExactJson(['success' => true]);

        $payment->refresh();
        $transaction->refresh();
        $this->assertSame('PAID', $payment->status);
        $this->assertSame('BCA Virtual Account', $payment->payment_name);
        $this->assertNotNull($payment->paid_at);
        $this->assertSame('lunas', $transaction->payment_status);
        $this->assertSame('tripay', $transaction->payment_method);
        $this->assertNotNull($transaction->paid_at);
        $this->assertSame(1, ActivityLog::where('action', 'payment_callback')->count());
    }

    public function test_callback_rejects_a_mismatched_amount(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $transaction = $this->createTransaction($cashier);
        $payment = $this->createPayment($transaction);
        $payload = $this->callbackPayload($payment);
        $payload['total_amount'] = 9_000;
        $json = json_encode($payload, JSON_THROW_ON_ERROR);

        $this->postCallback($json, hash_hmac('sha256', $json, 'sandbox-private-key'))
            ->assertStatus(409)
            ->assertJson(['success' => false]);

        $this->assertSame('UNPAID', $payment->fresh()->status);
        $this->assertSame('belum_lunas', $transaction->fresh()->payment_status);
    }

    public function test_sync_uses_sandbox_detail_and_applies_paid_status(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $transaction = $this->createTransaction($cashier);
        $payment = $this->createPayment($transaction);

        Http::fake([
            'https://tripay.co.id/api-sandbox/transaction/detail*' => Http::response([
                'success' => true,
                'data' => $this->providerData($payment->merchant_ref, 'PAID'),
            ]),
        ]);

        $this->actingAs($cashier)
            ->post(route('tripay-payments.sync', [$transaction, $payment]))
            ->assertRedirect();

        Http::assertSent(fn (ClientRequest $request) => $request->url()
            === 'https://tripay.co.id/api-sandbox/transaction/detail?reference=TRIPAY-REFERENCE-1');
        $transaction->refresh();
        $this->assertSame('PAID', $payment->fresh()->status);
        $this->assertSame('lunas', $transaction->payment_status);
        $this->assertSame('tripay', $transaction->payment_method);
    }

    public function test_transaction_with_tripay_history_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $transaction = $this->createTransaction($admin);
        $this->createPayment($transaction, ['status' => 'EXPIRED']);

        $this->actingAs($admin)
            ->delete(route('transactions.destroy', $transaction))
            ->assertSessionHasErrors('tripay');

        $this->assertDatabaseHas('transactions', ['id' => $transaction->id]);
    }

    private function createTransaction(User $user): Transaction
    {
        $customer = Customer::create([
            'name' => 'Pelanggan Tripay',
            'phone' => '081234567890',
        ]);
        $service = Service::create([
            'name' => 'Cuci Kiloan',
            'unit' => 'kg',
            'price' => 5_000,
            'is_active' => true,
        ]);
        $transaction = Transaction::create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'total' => 10_000,
            'payment_status' => 'belum_lunas',
            'work_status' => 'diterima',
        ]);
        TransactionItem::create([
            'transaction_id' => $transaction->id,
            'service_id' => $service->id,
            'service_name' => $service->name,
            'service_unit' => $service->unit,
            'quantity' => 2,
            'unit_price' => 5_000,
            'subtotal' => 10_000,
        ]);

        return $transaction->fresh(['customer', 'items']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createPayment(Transaction $transaction, array $attributes = []): TripayPayment
    {
        return TripayPayment::create([
            'transaction_id' => $transaction->id,
            'merchant_ref' => 'SCL-TEST-PAYMENT',
            'reference' => 'TRIPAY-REFERENCE-1',
            'payment_method' => 'BCAVA',
            'payment_name' => 'BCA Virtual Account',
            'customer_email' => 'pelanggan@example.com',
            'status' => 'UNPAID',
            'amount' => 10_000,
            'pay_code' => '1234567890',
            'checkout_url' => 'https://tripay.co.id/checkout/TRIPAY-REFERENCE-1',
            'expired_at' => now()->addHour(),
            ...$attributes,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function channel(): array
    {
        return [
            'group' => 'Virtual Account',
            'code' => 'BRIVA',
            'name' => 'BRI Virtual Account',
            'type' => 'direct',
            'total_fee' => ['flat' => 4_250, 'percent' => '0.00'],
            'minimum_amount' => 10_000,
            'maximum_amount' => 10_000_000,
            'active' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function providerData(string $merchantRef, string $status = 'UNPAID'): array
    {
        return [
            'reference' => 'TRIPAY-REFERENCE-1',
            'merchant_ref' => $merchantRef,
            'payment_method' => 'BRIVA',
            'payment_name' => 'BRI Virtual Account',
            'amount' => 10_000,
            'fee_merchant' => 4_250,
            'fee_customer' => 0,
            'total_fee' => 4_250,
            'amount_received' => 5_750,
            'pay_code' => '1234567890',
            'pay_url' => null,
            'checkout_url' => 'https://tripay.co.id/checkout/TRIPAY-REFERENCE-1',
            'status' => $status,
            'paid_at' => $status === 'PAID' ? now()->timestamp : null,
            'expired_time' => now()->addHour()->timestamp,
            'instructions' => [],
            'qr_string' => null,
            'qr_url' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function callbackPayload(TripayPayment $payment): array
    {
        return [
            'reference' => $payment->reference,
            'merchant_ref' => $payment->merchant_ref,
            'payment_method' => 'BCA Virtual Account',
            'payment_method_code' => 'BCAVA',
            'total_amount' => 10_000,
            'fee_merchant' => 4_250,
            'fee_customer' => 0,
            'total_fee' => 4_250,
            'amount_received' => 5_750,
            'is_closed_payment' => 1,
            'status' => 'PAID',
            'paid_at' => now()->timestamp,
            'note' => 'Pembayaran sukses',
        ];
    }

    private function postCallback(string $json, string $signature): TestResponse
    {
        return $this->call(
            'POST',
            route('tripay.callback'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_CALLBACK_SIGNATURE' => $signature,
                'HTTP_X_CALLBACK_EVENT' => 'payment_status',
            ],
            $json
        );
    }
}
