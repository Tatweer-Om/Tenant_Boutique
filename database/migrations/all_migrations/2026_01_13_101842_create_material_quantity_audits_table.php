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
        Schema::create('material_quantity_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('material_id');
            $table->string('material_name')->nullable();
            $table->string('operation_type')->default('added'); // 'added', 'quantity_added', 'sent_to_tailor'
            $table->decimal('previous_quantity', 10, 2)->default(0);
            $table->decimal('new_quantity', 10, 2)->default(0);
            $table->decimal('quantity_change', 10, 2)->default(0); // positive for add, negative for subtract
            $table->decimal('remaining_quantity', 10, 2)->default(0); // remaining quantity after operation
            $table->unsignedBigInteger('tailor_id')->nullable(); // if sent to tailor
            $table->string('tailor_name')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('added_by')->nullable(); // user name
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('material_id');
            $table->index('operation_type');
            $table->index('created_at');
            $table->foreign('material_id')->references('id')->on('materials')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_quantity_audits');
    }
};
