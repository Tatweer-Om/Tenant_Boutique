<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pos_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('order_no')->nullable();
            $table->decimal('paid_amount', 50, 3)->default(0);
            $table->decimal('total_amount', 50, 3)->default(0);
            $table->decimal('discount', 50, 3)->default(0);
            $table->unsignedBigInteger('account_id');
            $table->string('notes')->nullable();
            $table->string('added_by')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('pos_orders')) {
            Schema::table('pos_payments', function (Blueprint $table) {
                $table->foreign('order_id')->references('id')->on('pos_orders')->onDelete('cascade');
            });
        }
        if (Schema::hasTable('accounts')) {
            Schema::table('pos_payments', function (Blueprint $table) {
                $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('pos_payments', function (Blueprint $table) {
                $table->dropForeign(['order_id']);
            });
        } catch (\Throwable $e) {
            // Foreign key may not exist
        }
        try {
            Schema::table('pos_payments', function (Blueprint $table) {
                $table->dropForeign(['account_id']);
            });
        } catch (\Throwable $e) {
            // Foreign key may not exist
        }
        Schema::dropIfExists('pos_payments');
    }
};
