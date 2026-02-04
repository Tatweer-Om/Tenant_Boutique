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
        Schema::create('wharehouses', function (Blueprint $table) {
            $table->id();
               $table->string('wharehouse_name');
            $table->string('location');
            $table->longText('notes')->nullable();
            $table->string('user_id');
            $table->string('added_by');
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wharehouses');
    }
};
