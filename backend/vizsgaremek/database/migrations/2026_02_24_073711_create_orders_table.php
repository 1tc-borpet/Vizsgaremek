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
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('table_id')->nullable()->constrained('restaurant_tables')->onDelete('set null');
            $table->string('order_number')->unique();
            $table->enum('type', ['dine_in', 'takeaway', 'delivery'])->default('dine_in');
            $table->enum('status', ['pending', 'confirmed', 'preparing', 'ready', 'served', 'completed', 'cancelled'])->default('pending');
            $table->decimal('subtotal', 8, 2);
            $table->decimal('tax', 8, 2)->default(0);
            $table->decimal('delivery_fee', 8, 2)->default(0);
            $table->decimal('total', 8, 2);
            $table->text('notes')->nullable();
            $table->timestamp('estimated_completion_time')->nullable();
            $table->timestamp('completed_at')->nullable();
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
