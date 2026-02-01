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
            $table->string('tailor_order_no')->nullable()->after('sent_to_tailor_at');
            $table->index('tailor_order_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('special_order_items', function (Blueprint $table) {
            $table->dropIndex(['tailor_order_no']);
            $table->dropColumn('tailor_order_no');
        });
    }
};
