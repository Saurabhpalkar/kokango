<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Coupons (discount codes used at checkout) and product reviews written by customers who bought the product.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('description')->nullable();
            // percent: value is a percentage (1-90); fixed: value is an amount in paise
            $table->string('type', 10)->default('percent');
            $table->unsignedBigInteger('value');
            $table->unsignedBigInteger('min_subtotal_paise')->default(0);
            $table->unsignedBigInteger('max_discount_paise')->nullable();   // cap for percent coupons
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();             // total uses allowed (null = unlimited)
            $table->unsignedInteger('used_count')->default(0);
            $table->boolean('once_per_customer')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('coupon_code', 40)->nullable()->after('discount_paise');
        });

        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');                         // 1-5
            $table->string('title', 120)->nullable();
            $table->text('body');
            $table->string('status', 20)->default('published');            // published | hidden
            $table->timestamps();

            $table->unique(['product_id', 'user_id']);
            $table->index(['product_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('coupon_code');
        });
        Schema::dropIfExists('coupons');
    }
};
