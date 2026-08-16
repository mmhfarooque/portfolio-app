<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            // OAuth 1.0a credentials (X posting uses permanent 1.0a key pairs)
            $table->string('consumer_key')->nullable()->after('refresh_token');
            $table->string('consumer_secret')->nullable()->after('consumer_key');
            $table->string('token_secret')->nullable()->after('consumer_secret');
        });
    }

    public function down(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->dropColumn(['consumer_key', 'consumer_secret', 'token_secret']);
        });
    }
};
