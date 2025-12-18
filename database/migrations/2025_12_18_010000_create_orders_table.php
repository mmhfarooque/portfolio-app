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
            $table->string('order_number')->unique();
            $table->foreignId('photo_id')->constrained()->onDelete('cascade');
            $table->string('product_type'); // print, canvas, metal, license
            $table->string('product_name');
            $table->string('product_size')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('shipping', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('currency', 3)->default('USD');

            // Customer info
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();

            // Shipping address
            $table->string('shipping_address_line1')->nullable();
            $table->string('shipping_address_line2')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_state')->nullable();
            $table->string('shipping_postal_code')->nullable();
            $table->string('shipping_country', 2)->nullable();

            // Payment info
            $table->string('payment_provider')->default('stripe'); // stripe, paypal
            $table->string('payment_intent_id')->nullable();
            $table->string('payment_status')->default('pending'); // pending, paid, failed, refunded
            $table->timestamp('paid_at')->nullable();

            // Order status
            $table->string('status')->default('pending'); // pending, confirmed, processing, shipped, delivered, cancelled
            $table->string('tracking_number')->nullable();
            $table->string('tracking_url')->nullable();
            $table->text('notes')->nullable();

            // License specific (for digital licenses)
            $table->string('license_type')->nullable(); // personal, commercial, editorial
            $table->string('license_key')->nullable();
            $table->timestamp('license_expires_at')->nullable();
            $table->integer('download_count')->default(0);
            $table->integer('max_downloads')->default(5);

            $table->timestamps();

            $table->index(['customer_email', 'status']);
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
