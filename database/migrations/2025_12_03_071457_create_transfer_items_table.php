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
        Schema::create('transfer_items', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_id');
            $table->string('item_code')->nullable();
            $table->string('item_size')->nullable();
            $table->string('item_color')->nullable();
            $table->string('item_quantity')->nullable();
            $table->string('item_price')->nullable();
            $table->string('total_price')->nullable();
            $table->string('added_by')->nullable();
            $table->integer('user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_items');
    }
};
