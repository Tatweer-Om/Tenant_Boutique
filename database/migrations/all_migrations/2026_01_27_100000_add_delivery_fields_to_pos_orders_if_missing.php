<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Delivery columns (delivery_city_id, delivery_area_id, delivery_address, delivery_fee) already exist.
     * Only adds special_order_id to pos_orders if missing.
     */
    public function up(): void
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_orders', 'special_order_id')) {
                $table->unsignedBigInteger('special_order_id')->nullable()->after('id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            if (Schema::hasColumn('pos_orders', 'special_order_id')) {
                $table->dropColumn('special_order_id');
            }
        });
    }
};
