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
        if (Schema::hasTable('transfer_item_histories')) {
            return;
        }

        Schema::create('transfer_item_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transfer_id');
            $table->string('item_code')->nullable();
            $table->string('item_size')->nullable();
            $table->string('item_color')->nullable();
            $table->string('item_previous_quantity')->nullable();
            $table->string('quantity_action')->nullable();
            $table->string('item_new_quantity')->nullable();
            $table->integer('quantity_pulled')->default(0);
            $table->integer('quantity_pushed')->default(0);
            $table->string('added_by')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->foreign('transfer_id')->references('id')->on('transfers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_item_histories');
    }
};
