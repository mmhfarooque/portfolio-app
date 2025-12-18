<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['camera', 'lens', 'accessory', 'lighting', 'software'])->default('camera');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('affiliate_link')->nullable();
            $table->json('specifications')->nullable();
            $table->date('acquired_date')->nullable();
            $table->boolean('is_current')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['type', 'is_current']);
            $table->index('is_featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
