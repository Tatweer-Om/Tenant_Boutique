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
        if (Schema::hasTable('stock_audit_logs')) {
            return;
        }

        Schema::create('stock_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_id');
            $table->string('abaya_code')->nullable();
            $table->string('barcode')->nullable();
            $table->string('design_name')->nullable();
            $table->string('operation_type')->default('added');
            $table->integer('previous_quantity')->default(0);
            $table->integer('new_quantity')->default(0);
            $table->integer('quantity_change')->default(0);
            $table->string('related_id')->nullable();
            $table->string('related_type')->nullable();
            $table->text('related_info')->nullable();
            $table->unsignedBigInteger('color_id')->nullable();
            $table->unsignedBigInteger('size_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('added_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('stock_id');
            $table->index('abaya_code');
            $table->index('barcode');
            $table->index('operation_type');
            $table->index('created_at');

            if (Schema::hasTable('stocks')) {
                $table->foreign('stock_id')->references('id')->on('stocks')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('stock_audit_logs', function (Blueprint $table) {
                $table->dropForeign(['stock_id']);
            });
        } catch (\Throwable $e) {
            // Foreign key may not exist
        }
        Schema::dropIfExists('stock_audit_logs');
    }
};
