<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_visits', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->string('utm_source')->nullable()->index();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('referer')->nullable();
            $table->string('referer_domain')->nullable()->index();
            $table->string('landing_page');
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('device_type')->nullable(); // desktop, mobile, tablet
            $table->string('browser')->nullable();
            $table->string('os')->nullable();
            $table->string('country')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('converted')->default(false);
            $table->string('conversion_type')->nullable(); // order, contact, newsletter
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();

            $table->index(['created_at']);
            $table->index(['utm_source', 'utm_medium']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_visits');
    }
};
