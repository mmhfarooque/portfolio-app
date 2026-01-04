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
            // Per-photo watermark override - when true, this photo will NEVER have watermark
            // regardless of global settings (has "super power" priority)
            $table->boolean('watermark_disabled')->default(false)->after('custom_quality');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn('watermark_disabled');
        });
    }
};
