<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photo_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('photo_id')->constrained()->onDelete('cascade');
            $table->string('session_id', 100)->nullable();
            $table->string('ip_address', 45);
            $table->string('user_agent', 500)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();

            // Prevent duplicate likes: one like per session per photo
            $table->unique(['photo_id', 'session_id'], 'photo_session_unique');
            $table->index(['photo_id', 'ip_address']);
            $table->index('created_at');
        });

        // Add likes_count column to photos for performance (denormalized)
        Schema::table('photos', function (Blueprint $table) {
            $table->unsignedInteger('likes_count')->default(0)->after('views');
        });
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn('likes_count');
        });
        Schema::dropIfExists('photo_likes');
    }
};
