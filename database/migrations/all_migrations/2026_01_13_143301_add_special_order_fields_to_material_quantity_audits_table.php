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
        Schema::table('material_quantity_audits', function (Blueprint $table) {
            $table->unsignedBigInteger('special_order_id')->nullable()->after('status');
            $table->string('special_order_number')->nullable()->after('special_order_id');
            
            $table->index('special_order_id');
            $table->index('special_order_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('material_quantity_audits', function (Blueprint $table) {
            $table->dropIndex(['special_order_id']);
            $table->dropIndex(['special_order_number']);
            $table->dropColumn(['special_order_id', 'special_order_number']);
        });
    }
};
