<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('payment_method', ['tunai', 'transfer', 'qris', 'lainnya', 'tripay'])
                ->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('transactions')->where('payment_method', 'tripay')->exists()) {
            throw new RuntimeException('Cannot remove the tripay payment method while transactions use it.');
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('payment_method', ['tunai', 'transfer', 'qris', 'lainnya'])
                ->nullable()->change();
        });
    }
};
