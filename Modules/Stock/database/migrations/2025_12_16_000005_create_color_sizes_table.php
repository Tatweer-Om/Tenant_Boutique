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
        Schema::create('color_sizes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_id')->nullable();
            $table->unsignedBigInteger('color_id')->nullable();
            $table->unsignedBigInteger('size_id')->nullable();
            $table->integer('qty')->default(0);
            $table->timestamps();
        });

        if (Schema::hasTable('stocks')) {
            Schema::table('color_sizes', function (Blueprint $table) {
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
            Schema::table('color_sizes', function (Blueprint $table) {
                $table->dropForeign(['stock_id']);
            });
        } catch (\Throwable $e) {
            // Foreign key may not exist
        }
        Schema::dropIfExists('color_sizes');
    }
};
