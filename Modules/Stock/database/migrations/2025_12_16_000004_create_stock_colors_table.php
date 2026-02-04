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
        Schema::create('stock_colors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_id');
            $table->unsignedBigInteger('color_id');
            $table->integer('qty')->default(0);
            $table->timestamps();
        });

        if (Schema::hasTable('stocks')) {
            Schema::table('stock_colors', function (Blueprint $table) {
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
            Schema::table('stock_colors', function (Blueprint $table) {
                $table->dropForeign(['stock_id']);
            });
        } catch (\Throwable $e) {
            // Foreign key may not exist
        }
        Schema::dropIfExists('stock_colors');
    }
};
