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
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('area_id')->nullable();
            $table->string('city_name_en')->nullable();
            $table->string('city_name_ar')->nullable();
            $table->decimal('delivery_charges', 10, 3)->nullable();
            $table->text('notes')->nullable();
            $table->string('added_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('areas')) {
            Schema::table('cities', function (Blueprint $table) {
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
            Schema::table('cities', function (Blueprint $table) {
                $table->dropForeign(['area_id']);
            });
        } catch (\Throwable $e) {
            // Foreign key may not exist
        }
        Schema::dropIfExists('cities');
    }
};
