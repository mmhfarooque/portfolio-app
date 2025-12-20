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
            $table->dropColumn(['before_display_path', 'before_thumbnail_path']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->string('before_display_path')->nullable()->after('watermarked_path');
            $table->string('before_thumbnail_path')->nullable()->after('before_display_path');
        });
    }
};
