<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comment_otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('photo_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('photo_comments')->onDelete('cascade');
            $table->string('guest_name', 100);
            $table->string('guest_email', 255);
            $table->text('content');
            $table->string('otp_code', 6);
            $table->string('ip_address', 45);
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('expires_at');
            $table->integer('attempts')->default(0);
            $table->boolean('verified')->default(false);
            $table->timestamps();

            $table->index('guest_email');
            $table->index('otp_code');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_otp_verifications');
    }
};
