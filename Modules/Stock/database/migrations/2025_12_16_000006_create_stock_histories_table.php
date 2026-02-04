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
            $table->unsignedBigInteger('stock_id');
            $table->unsignedBigInteger('size_id')->nullable();
            $table->unsignedBigInteger('color_id')->nullable();
            $table->integer('old_qty')->default(0);
            $table->integer('changed_qty');
            $table->integer('new_qty')->default(0);
            $table->integer('action_type')->nullable();
            $table->unsignedBigInteger('tailor_id')->nullable();
            $table->longText('pull_notes')->nullable();
            $table->string('user_id')->nullable();
            $table->string('added_by')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('stocks')) {
            Schema::table('stock_histories', function (Blueprint $table) {
                $table->foreign('stock_id')->references('id')->on('stocks')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('stock_histories', function (Blueprint $table) {
                $table->dropForeign(['stock_id']);
            });
        } catch (\Throwable $e) {
            // Foreign key may not exist
        }
        Schema::dropIfExists('stock_histories');
    }
};
