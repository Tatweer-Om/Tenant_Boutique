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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            // Company Information
            $table->string('company_name')->nullable();
            $table->string('company_email')->nullable();
            $table->string('company_cr_no')->nullable();
            $table->string('company_logo')->nullable();
            $table->text('company_address')->nullable();
            // Late Delivery Settings
            $table->integer('late_delivery_weeks')->default(2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
