<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('customer_name', 120);
            $table->string('email', 120);
            $table->string('phone', 40);
            $table->unsignedInteger('quantity');
            $table->text('notes')->nullable();
            $table->string('locale', 10);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->string('currency', 20);
            $table->string('currency_position', 20)->default('prefix');
            $table->unsignedTinyInteger('price_decimals')->default(2);
            $table->string('status', 30)->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};