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
        // Add SEO fields to photos table
        Schema::table('photos', function (Blueprint $table) {
            $table->string('seo_title')->nullable()->after('title');
            $table->text('meta_description')->nullable()->after('description');
        });

        // Add SEO fields to categories table
        Schema::table('categories', function (Blueprint $table) {
            $table->string('seo_title')->nullable()->after('name');
            $table->text('meta_description')->nullable()->after('description');
        });

        // Add SEO fields to galleries table
        Schema::table('galleries', function (Blueprint $table) {
            $table->string('seo_title')->nullable()->after('name');
            $table->text('meta_description')->nullable()->after('description');
        });

        // Add SEO fields to tags table
        Schema::table('tags', function (Blueprint $table) {
            $table->string('seo_title')->nullable()->after('name');
            $table->text('meta_description')->nullable()->after('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn(['seo_title', 'meta_description']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['seo_title', 'meta_description']);
        });

        Schema::table('galleries', function (Blueprint $table) {
            $table->dropColumn(['seo_title', 'meta_description']);
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->dropColumn(['seo_title', 'meta_description']);
        });
    }
};
