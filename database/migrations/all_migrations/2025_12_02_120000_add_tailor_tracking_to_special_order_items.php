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
            $table->integer('tailor_id')->nullable()->after('notes');
            $table->string('tailor_status')->default('new')->after('tailor_id'); // new, processing, received
            $table->timestamp('sent_to_tailor_at')->nullable()->after('tailor_status');
            $table->timestamp('received_from_tailor_at')->nullable()->after('sent_to_tailor_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('special_order_items', function (Blueprint $table) {
            $table->dropColumn(['tailor_id', 'tailor_status', 'sent_to_tailor_at', 'received_from_tailor_at']);
        });
    }
};

