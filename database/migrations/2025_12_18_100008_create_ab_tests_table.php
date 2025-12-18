<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ab_tests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('type')->default('theme'); // theme, layout, content
            $table->string('status')->default('draft'); // draft, running, completed, paused
            $table->json('variants'); // [{name, value, weight}]
            $table->string('goal')->default('conversion'); // conversion, engagement, bounce
            $table->unsignedInteger('sample_size')->default(1000); // Target visitors
            $table->float('confidence_level')->default(95); // Statistical confidence
            $table->string('winner_variant')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ab_test_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ab_test_id')->constrained()->cascadeOnDelete();
            $table->string('visitor_id'); // Cookie-based identifier
            $table->string('variant'); // Assigned variant
            $table->boolean('converted')->default(false);
            $table->json('metadata')->nullable(); // Page viewed, time on page, etc.
            $table->timestamps();

            $table->unique(['ab_test_id', 'visitor_id']);
            $table->index('variant');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ab_test_participants');
        Schema::dropIfExists('ab_tests');
    }
};
