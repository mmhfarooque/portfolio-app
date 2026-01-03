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
        Schema::table('photos', function (Blueprint $table) {
            // Per-photo optimization settings (null = use global settings)
            $table->integer('custom_max_resolution')->nullable()->after('mime_type');
            $table->integer('custom_quality')->nullable()->after('custom_max_resolution');

            // Track original dimensions for reference
            $table->integer('original_width')->nullable()->after('custom_quality');
            $table->integer('original_height')->nullable()->after('original_width');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn([
                'custom_max_resolution',
                'custom_quality',
                'original_width',
                'original_height',
            ]);
        });
    }
};
