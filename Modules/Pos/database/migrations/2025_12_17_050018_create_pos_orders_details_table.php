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
        Schema::create('pos_orders_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('order_no')->nullable();
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('color_id')->nullable();
            $table->unsignedBigInteger('size_id')->nullable();
            $table->string('item_barcode')->nullable();
            $table->integer('item_quantity');
            $table->integer('restore_status')->nullable();
            $table->decimal('item_discount_price', 50, 3)->nullable();
            $table->decimal('item_price', 50, 3)->nullable();
            $table->decimal('item_total', 50, 3)->nullable();
            $table->decimal('item_tax', 50, 3)->nullable();
            $table->decimal('item_profit', 50, 3)->nullable();
            $table->string('added_by')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('channel_id')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('pos_orders')) {
            Schema::table('pos_orders_details', function (Blueprint $table) {
                $table->foreign('order_id')->references('id')->on('pos_orders')->onDelete('cascade');
            });
        }
        if (Schema::hasTable('channels')) {
            Schema::table('pos_orders_details', function (Blueprint $table) {
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
            Schema::table('pos_orders_details', function (Blueprint $table) {
                $table->dropForeign(['order_id']);
            });
        } catch (\Throwable $e) {
            // Foreign key may not exist
        }
        try {
            Schema::table('pos_orders_details', function (Blueprint $table) {
                $table->dropForeign(['channel_id']);
            });
        } catch (\Throwable $e) {
            // Foreign key may not exist
        }
        Schema::dropIfExists('pos_orders_details');
    }
};
