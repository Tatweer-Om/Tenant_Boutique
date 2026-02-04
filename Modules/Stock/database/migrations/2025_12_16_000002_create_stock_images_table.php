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
        Schema::create('stock_images', function (Blueprint $table) {
            $table->id();
            $table->string('image_path');
            $table->unsignedBigInteger('stock_id');
            $table->timestamps();
        });

        if (Schema::hasTable('stocks')) {
            Schema::table('stock_images', function (Blueprint $table) {
                $table->foreign('stock_id')->references('id')->on('stocks')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('stock_images', function (Blueprint $table) {
                $table->dropForeign(['stock_id']);
            });
        } catch (\Throwable $e) {
            // Foreign key may not exist
        }
        Schema::dropIfExists('stock_images');
    }
};
