<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marketplace: sellers (vendors) list their own products; Kokango reviews them, takes a commission
 * and pays the sellers out. Kokango's own products keep vendor_id = null and work exactly as before.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('store_name');
            $table->string('owner_name');
            $table->string('email');
            $table->string('phone', 15)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('pincode', 6)->nullable();
            $table->string('pickup_address')->nullable();
            $table->string('gst_no', 20)->nullable();
            $table->string('pan_no', 10)->nullable();
            $table->string('fssai_no', 20)->nullable();
            $table->text('description')->nullable();
            // pending | approved | suspended | rejected
            $table->string('status', 20)->default('pending');
            $table->string('status_reason')->nullable();
            // null = use the marketplace default commission
            $table->decimal('commission_percent', 5, 2)->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_no', 30)->nullable();
            $table->string('bank_ifsc', 11)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('vendor_id')->nullable()->after('category_id')->constrained('vendors')->nullOnDelete();
            // Vendor listings only: draft | pending_review | active | rejected | paused | archived (null for Kokango products)
            $table->string('status', 20)->nullable()->after('is_active');
            $table->string('review_note')->nullable()->after('status');
            $table->timestamp('submitted_at')->nullable()->after('review_note');
        });

        // Snapshot of the seller at checkout time, so a later product change never moves an order to another seller.
        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('vendor_id')->nullable()->after('product_variant_id')->index();
        });

        Schema::create('vendor_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->string('ref', 32)->nullable()->unique();
            $table->string('period', 60)->nullable();
            $table->unsignedBigInteger('amount_paise');
            // scheduled | processing | paid
            $table->string('status', 20)->default('scheduled');
            $table->string('utr', 50)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        // One row per (order, seller): the seller's part of a customer order, with its own fulfilment status.
        Schema::create('vendor_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            // pending | confirmed | packed | shipped | delivered | cancelled
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('subtotal_paise');
            $table->decimal('commission_percent', 5, 2);
            $table->unsignedBigInteger('commission_paise');
            $table->unsignedBigInteger('earning_paise');
            $table->foreignId('vendor_payout_id')->nullable()->constrained('vendor_payouts')->nullOnDelete();
            $table->timestamps();

            $table->unique(['order_id', 'vendor_id']);
            $table->index(['vendor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_orders');
        Schema::dropIfExists('vendor_payouts');

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['vendor_id']);
            $table->dropColumn('vendor_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vendor_id');
            $table->dropColumn(['status', 'review_note', 'submitted_at']);
        });

        Schema::dropIfExists('vendors');
    }
};
