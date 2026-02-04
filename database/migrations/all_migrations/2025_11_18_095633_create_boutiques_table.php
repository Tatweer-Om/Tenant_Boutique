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
        Schema::create('boutiques', function (Blueprint $table) {
            $table->id();
            $table->string('boutique_name');
            $table->string('shelf_no');
            $table->string('monthly_rent')->nullable();
            $table->date('rent_date')->nullable();
            $table->longText('boutique_address')->nullable();
            $table->string('status')
            ->nullable()
            ->comment("1 = Active, 2 = Inactive");
            $table->string('updated_by')->nullable();
            $table->string('added_by_by')->nullable();

            $table->integer('user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boutiques');
    }
};
