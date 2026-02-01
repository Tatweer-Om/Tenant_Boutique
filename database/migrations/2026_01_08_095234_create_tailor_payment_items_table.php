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
        if (Schema::hasTable('tailor_payment_items')) {
            return;
        }
        
        Schema::create('tailor_payment_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tailor_payment_id');
            $table->integer('tailor_id');
            $table->integer('stock_id')->nullable(); // For stock additions
            $table->integer('special_order_item_id')->nullable(); // For special orders
            $table->integer('stock_history_id')->nullable(); // Reference to stock_history entry
            $table->string('abaya_code');
            $table->integer('quantity');
            $table->decimal('unit_charge', 10, 3); // tailor_charges per piece
            $table->decimal('total_charge', 10, 3); // quantity * unit_charge
            $table->string('source'); // 'stock' or 'special_order'
            $table->timestamps();
            
            $table->foreign('tailor_payment_id')->references('id')->on('tailor_payments')->onDelete('cascade');
            // Note: Foreign keys removed to avoid constraint issues - data integrity maintained at application level
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tailor_payment_items');
    }
};
