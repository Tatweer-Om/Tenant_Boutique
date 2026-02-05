<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->string('channel_name_en');
            $table->string('channel_name_ar');
            $table->string('added_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('status_for_pos')->default(1)->comment('1 = active, 2 = inactive');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};
