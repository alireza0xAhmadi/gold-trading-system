<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['buy', 'sell']);
            $table->decimal('quantity', 10, 3); // in grams
            $table->decimal('remaining_quantity', 10, 3); // remaining quantity
            $table->bigInteger('price_per_gram'); // price in IRR (Rials)
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->timestamps();

            $table->index(['type', 'price_per_gram', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
}
