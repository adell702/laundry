<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tripay_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->string('merchant_ref', 100)->unique();
            $table->string('reference', 100)->nullable()->unique();
            $table->string('payment_method', 50);
            $table->string('payment_name')->nullable();
            $table->string('customer_email');
            $table->string('status', 20)->default('CREATING');
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('fee_merchant')->nullable();
            $table->unsignedBigInteger('fee_customer')->nullable();
            $table->unsignedBigInteger('total_fee')->nullable();
            $table->unsignedBigInteger('amount_received')->nullable();
            $table->string('pay_code')->nullable();
            $table->text('pay_url')->nullable();
            $table->text('checkout_url')->nullable();
            $table->longText('qr_string')->nullable();
            $table->text('qr_url')->nullable();
            $table->json('instructions')->nullable();
            $table->json('provider_data')->nullable();
            $table->json('last_callback')->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['transaction_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tripay_payments');
    }
};
