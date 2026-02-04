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
        Schema::table('special_orders', function (Blueprint $table) {
            $table->string('status')->default('new')->after('shipping_fee'); // new, processing, ready, delivered
            $table->decimal('total_amount', 10, 3)->default(0)->after('status');
            $table->decimal('paid_amount', 10, 3)->default(0)->after('total_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('special_orders', function (Blueprint $table) {
            $table->dropColumn(['status', 'total_amount', 'paid_amount']);
        });
    }
};

