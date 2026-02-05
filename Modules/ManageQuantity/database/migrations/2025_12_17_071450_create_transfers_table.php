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
        if (Schema::hasTable('transfers')) {
            return;
        }

        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_code');
            $table->string('transfer_type');
            $table->string('channel_type');
            $table->date('date');
            $table->integer('quantity')->default(0);
            $table->integer('sellable')->default(0)->nullable();
            $table->string('from');
            $table->string('to');
            $table->unsignedBigInteger('stock_id')->nullable();
            $table->unsignedBigInteger('boutique_id')->nullable();
            $table->unsignedBigInteger('channel_id')->nullable();
            $table->longText('notes')->nullable();
            $table->string('added_by')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
