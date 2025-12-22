<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('description');
            $table->decimal('amount', 15, 2); // Store in IDR (Base currency)
            $table->enum('type', ['income', 'expense'])->default('expense');
            $table->string('category')->nullable();
            $table->enum('frequency', ['weekly', 'monthly', 'yearly']);
            $table->date('start_date');
            $table->date('next_payment_date'); // Key field for automation
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_bills');
    }
};