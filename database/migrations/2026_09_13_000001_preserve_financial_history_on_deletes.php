<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->string('service_name')->nullable()->after('service_id');
            $table->string('service_unit', 50)->nullable()->after('service_name');
        });

        DB::table('transaction_items')
            ->orderBy('id')
            ->each(function (object $item): void {
                $service = DB::table('services')->find($item->service_id);

                if ($service) {
                    DB::table('transaction_items')
                        ->where('id', $item->id)
                        ->update([
                            'service_name' => $service->name,
                            'service_unit' => $service->unit,
                        ]);
                }
            });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('customer_id')->references('id')->on('customers')->restrictOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('transaction_items', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
        });

        Schema::table('transaction_items', function (Blueprint $table) {
            $table->unsignedBigInteger('service_id')->nullable()->change();
            $table->foreign('service_id')->references('id')->on('services')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('transaction_items', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
        });

        DB::table('transactions')->whereNull('user_id')->delete();
        DB::table('expenses')->whereNull('user_id')->delete();
        DB::table('transaction_items')->whereNull('service_id')->delete();

        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('transaction_items', function (Blueprint $table) {
            $table->unsignedBigInteger('service_id')->nullable(false)->change();
            $table->foreign('service_id')->references('id')->on('services')->cascadeOnDelete();
            $table->dropColumn(['service_name', 'service_unit']);
        });
    }
};
