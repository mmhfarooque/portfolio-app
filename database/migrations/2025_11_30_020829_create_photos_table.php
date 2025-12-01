<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // File paths
            $table->string('original_path')->nullable(); // No longer storing originals to save space
            $table->string('display_path')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->string('watermarked_path')->nullable();

            // Image metadata
            $table->json('exif_data')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('original_filename')->nullable();

            // Relationships
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('gallery_id')->nullable()->constrained()->onDelete('set null');

            // Status and visibility
            $table->enum('status', ['draft', 'published', 'processing'])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->unsignedBigInteger('views')->default(0);

            // Dates
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('is_featured');
            $table->index('captured_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};
