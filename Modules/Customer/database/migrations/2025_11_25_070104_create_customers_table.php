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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->unsignedBigInteger('area_id')->nullable();
            $table->text('address')->nullable();
            $table->longText('notes')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('cities')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->foreign('city_id')->references('id')->on('cities')->onDelete('set null');
            });
        }
        if (Schema::hasTable('areas')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->foreign('area_id')->references('id')->on('areas')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropForeign(['city_id']);
            });
        } catch (\Throwable $e) {
            // Foreign key may not exist
        }
        try {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropForeign(['area_id']);
            });
        } catch (\Throwable $e) {
            // Foreign key may not exist
        }
        Schema::dropIfExists('customers');
    }
};
