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
        Schema::create('special_order_items', function (Blueprint $table) {
            $table->id();
            $table->integer('special_order_id');
            $table->integer('stock_id')->nullable(); // Reference to stock/abaya
            $table->string('abaya_code')->nullable();
            $table->string('design_name')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('price', 10, 3)->default(0);
            $table->decimal('abaya_length', 8, 2)->nullable();
            $table->decimal('bust', 8, 2)->nullable();
            $table->decimal('sleeves_length', 8, 2)->nullable();
            $table->boolean('buttons')->default(true);
            $table->longText('notes')->nullable();
            $table->timestamps();

        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('special_order_items');
    }
};

