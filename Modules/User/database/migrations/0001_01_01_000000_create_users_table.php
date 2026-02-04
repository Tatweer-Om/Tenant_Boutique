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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('user_name');
            $table->string('user_phone');
            $table->string('user_email')->unique();
            $table->string('password');
            $table->longText('notes')->nullable();
            $table->longText('permissions')->nullable();
            $table->string('added_by')->nullable();
            $table->string('user_id')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });

         
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
