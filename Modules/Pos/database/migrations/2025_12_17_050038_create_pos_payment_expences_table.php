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
        Schema::create('pos_payment_expences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('order_no')->nullable();
            $table->decimal('total_amount', 50, 3)->nullable();
            $table->unsignedBigInteger('accoun_id')->nullable();
            $table->string('account_tax')->nullable();
            $table->decimal('account_tax_fee', 50, 3)->nullable();
            $table->string('added_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('pos_orders')) {
            Schema::table('pos_payment_expences', function (Blueprint $table) {
                $table->foreign('order_id')->references('id')->on('pos_orders')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('pos_payment_expences', function (Blueprint $table) {
                $table->dropForeign(['order_id']);
            });
        } catch (\Throwable $e) {
            // Foreign key may not exist
        }
        Schema::dropIfExists('pos_payment_expences');
    }
};
