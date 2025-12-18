<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('series', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('story')->nullable(); // Extended narrative
            $table->string('cover_image')->nullable();
            $table->date('project_date')->nullable();
            $table->string('location')->nullable();
            $table->string('status')->default('draft'); // draft, published
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('views')->default(0);
            $table->string('seo_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();

            $table->index(['status', 'is_featured']);
            $table->index('project_date');
        });

        Schema::create('photo_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('photo_id')->constrained()->cascadeOnDelete();
            $table->foreignId('series_id')->constrained()->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->text('caption')->nullable(); // Photo-specific caption within series
            $table->timestamps();

            $table->unique(['photo_id', 'series_id']);
            $table->index(['series_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photo_series');
        Schema::dropIfExists('series');
    }
};
