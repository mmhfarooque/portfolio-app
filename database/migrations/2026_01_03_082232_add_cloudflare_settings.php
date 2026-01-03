<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Setting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $cloudflareSettings = [
            // R2 Storage
            ['key' => 'r2_enabled', 'value' => '0', 'type' => 'boolean', 'group' => 'cloudflare'],
            ['key' => 'r2_access_key_id', 'value' => '', 'type' => 'text', 'group' => 'cloudflare'],
            ['key' => 'r2_secret_access_key', 'value' => '', 'type' => 'password', 'group' => 'cloudflare'],
            ['key' => 'r2_bucket', 'value' => 'photography', 'type' => 'text', 'group' => 'cloudflare'],
            ['key' => 'r2_endpoint', 'value' => '', 'type' => 'text', 'group' => 'cloudflare'],

            // Turnstile
            ['key' => 'turnstile_enabled', 'value' => '0', 'type' => 'boolean', 'group' => 'cloudflare'],
            ['key' => 'turnstile_site_key', 'value' => '', 'type' => 'text', 'group' => 'cloudflare'],
            ['key' => 'turnstile_secret_key', 'value' => '', 'type' => 'password', 'group' => 'cloudflare'],
        ];

        foreach ($cloudflareSettings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Setting::whereIn('key', [
            'r2_enabled',
            'r2_access_key_id',
            'r2_secret_access_key',
            'r2_bucket',
            'r2_endpoint',
            'turnstile_enabled',
            'turnstile_site_key',
            'turnstile_secret_key',
        ])->delete();
    }
};
