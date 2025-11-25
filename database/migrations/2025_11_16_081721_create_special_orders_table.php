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
        Schema::create('special_orders', function (Blueprint $table) {
            $table->id();
            $table->string('source'); // WhatsApp, Walk in
            $table->integer('customer_id'); // Foreign key to customers
            $table->boolean('send_as_gift')->default(false); // yes/no
            $table->longText('gift_text')->nullable(); // gift text if any
            $table->longText('notes')->nullable(); // general notes
            $table->decimal('shipping_fee', 10, 3)->default(0);
            $table->integer('user_id');
            $table->string('added_by');
            $table->string('updated_by')->nullable(); 
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('special_orders');
    }
};
