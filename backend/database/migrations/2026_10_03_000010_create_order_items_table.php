<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            // Snapshot references: deliberately no FK so catalogue rows can be removed later.
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->unsignedBigInteger('product_variant_id')->nullable()->index();
            $table->string('product_slug')->nullable();
            $table->string('name');
            $table->string('size_label', 50);
            $table->string('image')->nullable();
            $table->unsignedBigInteger('unit_price_paise');
            $table->unsignedInteger('qty');
            $table->unsignedBigInteger('total_paise');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
