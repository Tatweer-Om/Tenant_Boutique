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
        Schema::create('pos_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('special_order_id')->nullable();
            $table->string('order_type')->nullable();
            $table->string('delivery_status')->nullable();
            $table->integer('item_count');
            $table->decimal('paid_amount', 50, 3);
            $table->decimal('total_amount', 50, 3);
            $table->string('discount_type')->nullable();
            $table->decimal('total_discount', 50, 3)->nullable();
            $table->decimal('delivery_charges', 10, 3)->default(0);
            $table->boolean('delivery_paid')->default(false);
            $table->unsignedBigInteger('delivery_city_id')->nullable();
            $table->unsignedBigInteger('delivery_area_id')->nullable();
            $table->text('delivery_address')->nullable();
            $table->decimal('delivery_fee', 10, 3)->nullable();
            $table->boolean('delivery_fee_paid')->default(false);
            $table->decimal('profit', 50, 3)->nullable();
            $table->integer('return_status')->default(0)->nullable();
            $table->integer('restore_status')->default(0)->nullable();
            $table->string('order_no')->nullable();
            $table->longText('notes')->nullable();
            $table->string('added_by')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('channel_id')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('customers')) {
            Schema::table('pos_orders', function (Blueprint $table) {
                $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            });
        }
        if (Schema::hasTable('channels')) {
            Schema::table('pos_orders', function (Blueprint $table) {
                $table->foreign('channel_id')->references('id')->on('channels')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('pos_orders', function (Blueprint $table) {
                $table->dropForeign(['customer_id']);
            });
        } catch (\Throwable $e) {
            // Foreign key may not exist
        }
        try {
            Schema::table('pos_orders', function (Blueprint $table) {
                $table->dropForeign(['channel_id']);
            });
        } catch (\Throwable $e) {
            // Foreign key may not exist
        }
        Schema::dropIfExists('pos_orders');
    }
};
