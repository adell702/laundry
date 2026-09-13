<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class PaymentMethodMigrationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_upgrade_preserves_existing_transactions_and_accepts_tripay(): void
    {
        $migration = $this->migration();
        $migration->down();

        foreach (['tunai', 'transfer', 'qris', 'lainnya', null] as $method) {
            $this->createTransaction($method);
        }
        $before = DB::table('transactions')->orderBy('id')->get()->all();

        $migration->up();

        $this->assertEquals($before, DB::table('transactions')->orderBy('id')->get()->all());
        $payment = $this->createTransaction('tripay');
        try {
            $this->assertSame('tripay', $payment->fresh()->payment_method);
            $this->assertNull($this->createTransaction(null)->fresh()->payment_method);
        } finally {
            $payment->delete();
        }
    }

    public function test_rollback_refuses_to_discard_existing_tripay_payment_methods(): void
    {
        $payment = $this->createTransaction('tripay');
        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Cannot remove the tripay payment method while transactions use it.');
            $this->migration()->down();
        } finally {
            $this->assertSame('tripay', $payment->fresh()->payment_method);
            $payment->delete();
        }
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_09_13_000004_allow_tripay_transaction_payment_method.php');
    }

    private function createTransaction(?string $method): Transaction
    {
        return Transaction::create([
            'customer_id' => Customer::firstOrCreate(['phone' => '081234567890'], ['name' => 'Migration test'])->id,
            'user_id' => User::factory()->create()->id,
            'total' => 10_000,
            'payment_status' => 'belum_lunas',
            'payment_method' => $method,
            'work_status' => 'diterima',
        ]);
    }
}
