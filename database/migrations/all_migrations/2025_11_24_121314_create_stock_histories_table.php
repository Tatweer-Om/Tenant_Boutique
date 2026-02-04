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
        Schema::create('stock_histories', function (Blueprint $table) {
            $table->id();
                $table->integer('stock_id');
                $table->integer('size_id')->nullable();
                $table->integer('color_id')->nullable();
                $table->integer('old_qty')->default(0);
                $table->integer('changed_qty');   
                $table->integer('new_qty')->default(0);
                $table->integer('action_type')->nullable(); 
                $table->longText('pull_notes')->nullable(); 
                $table->string('user_id')->nullable();
                $table->string('added_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_histories');
    }
};
