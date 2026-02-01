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
        Schema::table('special_order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('color_id')->nullable()->after('stock_id');
            $table->unsignedBigInteger('size_id')->nullable()->after('color_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('special_order_items', function (Blueprint $table) {
            $table->dropColumn(['color_id', 'size_id']);
        });
    }
};
