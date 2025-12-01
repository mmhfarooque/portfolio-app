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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // Log type: error, warning, info, debug, activity
            $table->string('type', 20)->default('info');

            // Log level for filtering: critical, error, warning, notice, info, debug
            $table->string('level', 20)->default('info');

            // Action/event description
            $table->string('action');

            // Detailed message
            $table->text('message')->nullable();

            // Context data (JSON)
            $table->json('context')->nullable();

            // User who triggered the action
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');

            // Related model (polymorphic)
            $table->string('loggable_type')->nullable();
            $table->unsignedBigInteger('loggable_id')->nullable();

            // Request details
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->string('method', 10)->nullable();

            // Performance metrics
            $table->float('duration_ms')->nullable();
            $table->unsignedInteger('memory_usage')->nullable();

            // Error details
            $table->string('exception_class')->nullable();
            $table->text('exception_message')->nullable();
            $table->text('exception_trace')->nullable();
            $table->string('file')->nullable();
            $table->unsignedInteger('line')->nullable();

            $table->timestamps();

            // Indexes for efficient querying
            $table->index('type');
            $table->index('level');
            $table->index('action');
            $table->index('created_at');
            $table->index(['loggable_type', 'loggable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
