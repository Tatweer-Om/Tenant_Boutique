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
            $table->decimal('previous_tailor_material_quantity', 10, 2)->nullable()->default(0)->after('tailor_material_quantity_deducted');
            $table->decimal('new_tailor_material_quantity', 10, 2)->nullable()->default(0)->after('previous_tailor_material_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('material_quantity_audits', function (Blueprint $table) {
            $table->dropColumn(['previous_tailor_material_quantity', 'new_tailor_material_quantity']);
        });
    }
};
