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
        Schema::create('sizes', function (Blueprint $table) {
            $table->id();
             $table->string('size_name_en'); // Size name
            $table->string('size_name_ar'); // Size name
            $table->string('size_code_en')->nullable();
            $table->string('size_code_ar')->nullable();
            $table->string('added_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->integer('user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sizes');
    }
};
