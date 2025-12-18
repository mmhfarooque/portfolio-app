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
            $table->string('image_hash', 64)->nullable()->after('file_size');
            $table->string('file_hash', 64)->nullable()->after('image_hash');
            $table->index('image_hash');
            $table->index('file_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropIndex(['image_hash']);
            $table->dropIndex(['file_hash']);
            $table->dropColumn(['image_hash', 'file_hash']);
        });
    }
};
