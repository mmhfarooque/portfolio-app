<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('galleries', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('sort_order');
            $table->string('client_name')->nullable()->after('expires_at');
            $table->string('client_email')->nullable()->after('client_name');
            $table->string('access_token', 64)->nullable()->unique()->after('client_email');
            $table->boolean('is_client_gallery')->default(false)->after('access_token');
            $table->boolean('allow_downloads')->default(false)->after('is_client_gallery');
            $table->boolean('allow_selections')->default(true)->after('allow_downloads');
            $table->integer('selection_limit')->nullable()->after('allow_selections');
            $table->text('client_notes')->nullable()->after('selection_limit');
            $table->timestamp('last_accessed_at')->nullable()->after('client_notes');
            $table->integer('view_count')->default(0)->after('last_accessed_at');

            $table->index('access_token');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('galleries', function (Blueprint $table) {
            $table->dropIndex(['access_token']);
            $table->dropIndex(['expires_at']);
            $table->dropColumn([
                'expires_at',
                'client_name',
                'client_email',
                'access_token',
                'is_client_gallery',
                'allow_downloads',
                'allow_selections',
                'selection_limit',
                'client_notes',
                'last_accessed_at',
                'view_count',
            ]);
        });
    }
};
