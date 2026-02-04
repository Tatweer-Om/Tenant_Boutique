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
            $table->string('maintenance_transfer_number')->nullable()->after('repaired_delivered_at');
            $table->decimal('maintenance_delivery_charges', 10, 3)->default(0)->after('maintenance_transfer_number');
            $table->decimal('maintenance_repair_cost', 10, 3)->default(0)->after('maintenance_delivery_charges');
            $table->string('maintenance_cost_bearer')->nullable()->after('maintenance_repair_cost'); // customer, company
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('special_order_items', function (Blueprint $table) {
            $table->dropColumn([
                'maintenance_transfer_number',
                'maintenance_delivery_charges',
                'maintenance_repair_cost',
                'maintenance_cost_bearer'
            ]);
        });
    }
};

