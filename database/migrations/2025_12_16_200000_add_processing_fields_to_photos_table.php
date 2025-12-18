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
            $table->string('processing_stage')->nullable()->after('status');
            $table->text('processing_error')->nullable()->after('processing_stage');
        });

        // Add 'failed' to status enum if not exists
        // Note: In MySQL, we'd need to modify the enum. For SQLite/PostgreSQL, varchar works fine.
        // Laravel 12 uses varchar for status by default.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn(['processing_stage', 'processing_error']);
        });
    }
};
