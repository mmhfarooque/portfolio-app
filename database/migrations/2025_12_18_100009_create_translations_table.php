<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->morphs('translatable'); // For photo_id/post_id polymorphic
            $table->string('locale', 5); // en, es, fr, de, ja
            $table->string('field'); // title, description, content
            $table->text('value');
            $table->timestamps();

            $table->unique(['translatable_type', 'translatable_id', 'locale', 'field']);
        });

        // Add locale preference to users
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'locale')) {
                $table->string('locale', 5)->default('en')->after('remember_token');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'locale')) {
                $table->dropColumn('locale');
            }
        });
    }
};
