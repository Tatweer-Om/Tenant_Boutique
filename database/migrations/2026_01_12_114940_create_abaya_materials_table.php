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
        Schema::create('abaya_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('abaya_id');
            $table->string('abaya_barcode')->nullable();
            $table->json('materials'); // Array of {material_id, quantity, unit}
            $table->timestamps();
            
            $table->foreign('abaya_id')->references('id')->on('stocks')->onDelete('cascade');
            $table->index('abaya_id');
            $table->index('abaya_barcode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abaya_materials');
    }
};
