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
        Schema::create('client_selections', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 64)->index();
            $table->foreignId('photo_id')->constrained()->onDelete('cascade');
            $table->foreignId('gallery_id')->nullable()->constrained()->onDelete('set null');
            $table->text('note')->nullable();
            $table->timestamps();

            // Prevent duplicate selections per session
            $table->unique(['session_id', 'photo_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_selections');
    }
};
