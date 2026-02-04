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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->string('abaya_code')->nullable();
            $table->string('design_name')->nullable();
            $table->string('barcode')->nullable();
            $table->longText('abaya_notes')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->decimal('cost_price', 10, 3)->nullable();
            $table->decimal('sales_price', 10, 3)->nullable();
            $table->decimal('tailor_charges', 10, 3)->nullable();
            $table->string('tailor_id')->nullable();
            $table->string('quantity')->nullable();
            $table->string('status')->nullable();
            $table->tinyInteger('website_data_delivery_status')->default(0)->nullable();
            $table->integer('notification_limit')->nullable();
            $table->string('mode')->nullable();
            $table->string('added_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('user_id')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('categories')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('stocks', function (Blueprint $table) {
                $table->dropForeign(['category_id']);
            });
        } catch (\Throwable $e) {
            // Foreign key may not exist if categories table was missing
        }
        Schema::dropIfExists('stocks');
    }
};
