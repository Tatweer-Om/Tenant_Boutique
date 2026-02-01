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
            $table->decimal('tailor_material_quantity_deducted', 10, 2)->nullable()->default(0)->after('remaining_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('material_quantity_audits', function (Blueprint $table) {
            $table->dropColumn('tailor_material_quantity_deducted');
        });
    }
};
