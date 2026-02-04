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
            $table->string('maintenance_status')->nullable()->after('received_from_tailor_at'); // under_repair, repaired, repaired_delivered
            $table->integer('maintenance_tailor_id')->nullable()->after('maintenance_status');
            $table->timestamp('sent_for_repair_at')->nullable()->after('maintenance_tailor_id');
            $table->timestamp('repaired_at')->nullable()->after('sent_for_repair_at');
            $table->timestamp('repaired_delivered_at')->nullable()->after('repaired_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('special_order_items', function (Blueprint $table) {
            $table->dropColumn([
                'maintenance_status',
                'maintenance_tailor_id',
                'sent_for_repair_at',
                'repaired_at',
                'repaired_delivered_at'
            ]);
        });
    }
};
