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
        Schema::table('tailor_materials', function (Blueprint $table) {
            $table->unsignedBigInteger('abaya_id')->nullable()->after('material_id');
            $table->foreign('abaya_id')->references('id')->on('stocks')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tailor_materials', function (Blueprint $table) {
            $table->dropForeign(['abaya_id']);
            $table->dropColumn('abaya_id');
        });
    }
};
