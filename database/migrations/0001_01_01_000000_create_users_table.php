<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // user's full name
            $table->string('email')->unique(); // unique email address
            $table->decimal('gold_balance', 10, 3)->default(0); // gold balance in grams
            $table->bigInteger('rial_balance')->default(0); // Rial balance (stored in IRR)
            $table->timestamps(); // created_at & updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
}
