<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('image');
            $table->string('sku')->unique();
            $table->decimal('price_value', 10, 2);
            $table->string('currency', 20)->default('$');
            $table->string('currency_position', 20)->default('prefix');
            $table->unsignedTinyInteger('price_decimals')->default(2);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('translations');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};