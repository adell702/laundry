<?php

namespace Tests\Feature;

use App\Exports\TransactionsExport;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class LaundryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_cannot_delete_transaction(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $transaction = $this->createTransaction($cashier);

        $this->actingAs($cashier)
            ->delete(route('transactions.destroy', $transaction))
            ->assertForbidden();

        $this->assertDatabaseHas('transactions', ['id' => $transaction->id]);
    }

    public function test_customer_with_transaction_history_cannot_be_deleted(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $transaction = $this->createTransaction($cashier);

        $this->actingAs($cashier)
            ->from(route('customers.show', $transaction->customer))
            ->delete(route('customers.destroy', $transaction->customer))
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('customers', ['id' => $transaction->customer_id]);
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id]);
    }

    public function test_deleting_employee_preserves_financial_records(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cashier = User::factory()->create(['role' => 'kasir']);
        $transaction = $this->createTransaction($cashier);
        $expense = Expense::create([
            'title' => 'Sabun',
            'amount' => 25_000,
            'expense_date' => now()->toDateString(),
            'user_id' => $cashier->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('users.destroy', $cashier))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('users', ['id' => $cashier->id]);
        $this->assertNull($transaction->fresh()->user_id);
        $this->assertNull($expense->fresh()->user_id);
    }

    public function test_deleting_service_preserves_historical_line_item(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $transaction = $this->createTransaction($admin);
        $service = Service::create([
            'name' => 'Cuci Khusus',
            'unit' => 'kg',
            'price' => 15_000,
            'is_active' => true,
        ]);
        $item = TransactionItem::create([
            'transaction_id' => $transaction->id,
            'service_id' => $service->id,
            'service_name' => $service->name,
            'service_unit' => $service->unit,
            'quantity' => 2,
            'unit_price' => $service->price,
            'subtotal' => 30_000,
        ]);

        $this->actingAs($admin)
            ->delete(route('services.destroy', $service))
            ->assertRedirect(route('services.index'));

        $item->refresh();
        $this->assertNull($item->service_id);
        $this->assertSame('Cuci Khusus', $item->service_name);
        $this->assertSame('kg', $item->service_unit);
    }

    public function test_inactive_service_used_by_transaction_is_available_when_editing(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $transaction = $this->createTransaction($cashier);
        $service = Service::create([
            'name' => 'Layanan Lama',
            'unit' => 'kg',
            'price' => 10_000,
            'is_active' => false,
        ]);
        TransactionItem::create([
            'transaction_id' => $transaction->id,
            'service_id' => $service->id,
            'service_name' => $service->name,
            'service_unit' => $service->unit,
            'quantity' => 1,
            'unit_price' => $service->price,
            'subtotal' => $service->price,
        ]);

        $this->actingAs($cashier)
            ->get(route('transactions.edit', $transaction))
            ->assertOk()
            ->assertViewHas('services', fn ($services) => $services->contains('id', $service->id));
    }

    public function test_transaction_search_remains_grouped_with_status_filter(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $unpaid = $this->createTransaction($cashier, [
            'invoice_code' => 'MATCH-UNPAID',
            'payment_status' => 'belum_lunas',
        ]);
        $matchingCustomer = Customer::create([
            'name' => 'Match Customer',
            'phone' => '081200000002',
        ]);
        $paid = $this->createTransaction($cashier, [
            'customer_id' => $matchingCustomer->id,
            'invoice_code' => 'OTHER-PAID',
            'payment_status' => 'lunas',
            'paid_at' => now(),
        ]);

        $this->actingAs($cashier)
            ->get(route('transactions.index', ['search' => 'MATCH', 'payment_status' => 'lunas']))
            ->assertOk()
            ->assertViewHas('transactions', function ($transactions) use ($paid, $unpaid) {
                return $transactions->contains('id', $paid->id)
                    && ! $transactions->contains('id', $unpaid->id);
            });
    }

    public function test_expense_total_uses_search_filter(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        Expense::create([
            'title' => 'Sabun khusus',
            'amount' => 10_000,
            'expense_date' => now()->toDateString(),
            'user_id' => $cashier->id,
        ]);
        Expense::create([
            'title' => 'Listrik',
            'amount' => 90_000,
            'expense_date' => now()->toDateString(),
            'user_id' => $cashier->id,
        ]);

        $this->actingAs($cashier)
            ->get(route('expenses.index', ['search' => 'Sabun']))
            ->assertOk()
            ->assertViewHas('total', fn ($total) => (float) $total === 10_000.0);
    }

    public function test_status_rollback_clears_completion_and_payment_metadata(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $transaction = $this->createTransaction($cashier, [
            'work_status' => 'selesai',
            'completed_at' => now(),
            'payment_status' => 'lunas',
            'payment_method' => 'qris',
            'paid_at' => now(),
        ]);

        $this->actingAs($cashier)
            ->patch(route('transactions.status', $transaction), [
                'work_status' => 'dicuci',
                'payment_status' => 'belum_lunas',
            ])
            ->assertRedirect();

        $transaction->refresh();
        $this->assertNull($transaction->completed_at);
        $this->assertNull($transaction->paid_at);
        $this->assertNull($transaction->payment_method);
    }

    public function test_dashboard_and_report_book_income_on_payment_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-13 12:00:00', 'Asia/Jakarta'));
        $admin = User::factory()->create(['role' => 'admin']);
        $paidToday = $this->createTransaction($admin, [
            'total' => 40_000,
            'payment_status' => 'lunas',
            'paid_at' => now(),
        ]);
        $paidToday->forceFill(['created_at' => now()->subDay()])->saveQuietly();
        $paidYesterday = $this->createTransaction($admin, [
            'total' => 90_000,
            'payment_status' => 'lunas',
            'paid_at' => now()->subDay(),
        ]);
        $paidYesterday->forceFill(['created_at' => now()])->saveQuietly();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('todayIncome', fn ($income) => (float) $income === 40_000.0);

        $this->actingAs($admin)
            ->get(route('reports.index', [
                'date_from' => now()->toDateString(),
                'date_to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertViewHas('income', fn ($income) => (float) $income === 40_000.0);
    }

    public function test_export_binds_user_text_as_plain_string(): void
    {
        $spreadsheet = new Spreadsheet;
        $cell = $spreadsheet->getActiveSheet()->getCell('A1');
        $export = new TransactionsExport('2026-09-01', '2026-09-30');

        $export->bindValue($cell, '=HYPERLINK("https://example.test")');

        $this->assertSame(DataType::TYPE_STRING, $cell->getDataType());
    }

    public function test_indonesian_phone_is_normalized_for_whatsapp(): void
    {
        $customer = new Customer(['phone' => '0812-3456-7890']);

        $this->assertSame('6281234567890', $customer->whatsappNumber());
    }

    private function createTransaction(User $user, array $attributes = []): Transaction
    {
        $customerId = $attributes['customer_id'] ?? Customer::create([
            'name' => 'Pelanggan '.fake()->unique()->numerify('####'),
            'phone' => fake()->unique()->numerify('0812########'),
        ])->id;

        unset($attributes['customer_id']);

        return Transaction::create([
            'customer_id' => $customerId,
            'user_id' => $user->id,
            'total' => 10_000,
            'payment_status' => 'belum_lunas',
            'work_status' => 'diterima',
            ...$attributes,
        ]);
    }
}
