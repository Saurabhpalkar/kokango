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
            $table->string('order_no', 32)->nullable()->unique();
            $table->string('public_token', 40)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone', 15)->nullable();

            $table->string('ship_name');
            $table->string('ship_phone', 15);
            $table->string('ship_line1');
            $table->string('ship_line2')->nullable();
            $table->string('ship_city');
            $table->string('ship_state');
            $table->string('ship_pincode', 6);

            $table->string('shipping_method', 20)->default('standard');
            $table->unsignedBigInteger('subtotal_paise');
            $table->unsignedBigInteger('shipping_paise')->default(0);
            $table->unsignedBigInteger('tax_paise')->default(0);
            $table->unsignedBigInteger('discount_paise')->default(0);
            $table->unsignedBigInteger('total_paise');

            $table->string('status', 20)->default('pending');
            $table->string('payment_status', 20)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('payment_status');
            $table->index('customer_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
