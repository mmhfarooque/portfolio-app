<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photo_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('photo_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('photo_comments')->onDelete('cascade');

            // Guest info (required for guests)
            $table->string('guest_name', 100)->nullable();
            $table->string('guest_email', 255)->nullable();

            // Optional: for registered users (admin replies)
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');

            // Content
            $table->text('content');

            // Moderation
            $table->enum('status', ['pending', 'approved', 'rejected', 'spam'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');

            // Spam protection
            $table->string('ip_address', 45);
            $table->string('user_agent', 500)->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['photo_id', 'status']);
            $table->index('parent_id');
            $table->index('guest_email');
            $table->index('status');
            $table->index('created_at');
        });

        // Add comments_count to photos for performance
        Schema::table('photos', function (Blueprint $table) {
            $table->unsignedInteger('comments_count')->default(0)->after('likes_count');
        });
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn('comments_count');
        });
        Schema::dropIfExists('photo_comments');
    }
};
