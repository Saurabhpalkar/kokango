<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('gateway', 20);
            $table->string('gateway_order_id')->nullable()->index();
            $table->string('gateway_payment_id')->nullable();
            $table->text('signature')->nullable();
            $table->string('method', 30)->nullable();
            $table->unsignedBigInteger('amount_paise');
            $table->string('currency', 3)->default('INR');
            $table->string('status', 20)->default('pending');
            $table->json('payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
