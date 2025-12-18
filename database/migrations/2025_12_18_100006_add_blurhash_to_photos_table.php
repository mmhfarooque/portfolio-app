<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->string('blurhash', 50)->nullable()->after('image_hash');
            $table->string('dominant_color', 7)->nullable()->after('blurhash'); // Hex color
        });
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn(['blurhash', 'dominant_color']);
        });
    }
};
