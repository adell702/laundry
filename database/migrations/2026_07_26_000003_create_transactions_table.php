<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_code')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('total', 14, 2)->default(0);
            $table->enum('payment_status', ['belum_lunas', 'lunas'])->default('belum_lunas');
            $table->enum('payment_method', ['tunai', 'transfer', 'qris', 'lainnya'])->nullable();
            $table->enum('work_status', [
                'diterima',
                'dicuci',
                'disetrika',
                'selesai',
                'diambil',
            ])->default('diterima');
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('estimated_ready_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
