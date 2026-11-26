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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no')->nullable()->unique();
            $table->foreignId('product_id')->constrained();
            $table->unsignedInteger('quantity');
            $table->foreignId('from_location_id')->constrained('locations');
            $table->foreignId('to_location_id')->constrained('locations');
            $table->string('status')->default('Pending');
            $table->foreignId('requested_by')->constrained('users');
            $table->text('reject_reason')->nullable();
            $table->unsignedInteger('received_quantity')->nullable();
            $table->string('received_condition')->nullable(); // Good or Damaged
            $table->string('received_notes')->nullable(); // for example: one leg cracked
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
