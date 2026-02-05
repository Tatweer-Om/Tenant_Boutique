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
        if (Schema::hasTable('channel_stocks')) {
            return;
        }

        Schema::create('channel_stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_id');
            $table->string('location_type');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('abaya_code');
            $table->string('item_type')->nullable();
            $table->unsignedBigInteger('color_id')->nullable();
            $table->unsignedBigInteger('size_id')->nullable();
            $table->string('color_name')->nullable();
            $table->string('size_name')->nullable();
            $table->integer('quantity')->default(0);
            $table->timestamps();

            $table->index(['location_type', 'location_id'], 'ch_stocks_loc_idx');
            $table->index(['stock_id', 'location_type', 'location_id', 'color_id', 'size_id'], 'ch_stocks_lookup_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channel_stocks');
    }
};
