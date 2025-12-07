<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Branding
            ['key' => 'site_name', 'value' => 'Photography Portfolio', 'type' => 'text', 'group' => 'branding'],
            ['key' => 'site_tagline', 'value' => 'Capturing Moments in Time', 'type' => 'text', 'group' => 'branding'],
            ['key' => 'site_logo', 'value' => null, 'type' => 'image', 'group' => 'branding'],
            ['key' => 'photographer_name', 'value' => 'Your Name', 'type' => 'text', 'group' => 'branding'],
            ['key' => 'hero_title', 'value' => 'Capturing Moments', 'type' => 'text', 'group' => 'branding'],
            ['key' => 'hero_subtitle', 'value' => 'Landscape & Nature Photography', 'type' => 'text', 'group' => 'branding'],

            // Social Media
            ['key' => 'social_instagram', 'value' => '', 'type' => 'text', 'group' => 'social'],
            ['key' => 'social_facebook', 'value' => '', 'type' => 'text', 'group' => 'social'],
            ['key' => 'social_twitter', 'value' => '', 'type' => 'text', 'group' => 'social'],
            ['key' => 'social_youtube', 'value' => '', 'type' => 'text', 'group' => 'social'],
            ['key' => 'social_flickr', 'value' => '', 'type' => 'text', 'group' => 'social'],
            ['key' => 'social_500px', 'value' => '', 'type' => 'text', 'group' => 'social'],

            // Watermark
            ['key' => 'watermark_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'watermark'],
            ['key' => 'watermark_text', 'value' => '© Your Name', 'type' => 'text', 'group' => 'watermark'],
            ['key' => 'watermark_position', 'value' => 'bottom-right', 'type' => 'select', 'group' => 'watermark'],
            ['key' => 'watermark_opacity', 'value' => '50', 'type' => 'number', 'group' => 'watermark'],
            ['key' => 'watermark_size', 'value' => '24', 'type' => 'number', 'group' => 'watermark'],

            // Contact
            ['key' => 'contact_email', 'value' => '', 'type' => 'text', 'group' => 'contact'],
            ['key' => 'contact_phone', 'value' => '', 'type' => 'text', 'group' => 'contact'],
            ['key' => 'contact_location', 'value' => '', 'type' => 'text', 'group' => 'contact'],
            ['key' => 'contact_availability', 'value' => 'Available for bookings', 'type' => 'text', 'group' => 'contact'],

            // Profile / Front Page
            ['key' => 'profile_image', 'value' => null, 'type' => 'image', 'group' => 'profile'],
            ['key' => 'profile_name', 'value' => 'Your Name', 'type' => 'text', 'group' => 'profile'],
            ['key' => 'profile_title', 'value' => 'Web Developer & Landscape Photographer', 'type' => 'text', 'group' => 'profile'],
            ['key' => 'profile_tagline', 'value' => 'Creating digital experiences and capturing natural beauty', 'type' => 'text', 'group' => 'profile'],
            ['key' => 'profile_bio', 'value' => null, 'type' => 'editorjs', 'group' => 'profile'],
            ['key' => 'profile_location', 'value' => '', 'type' => 'text', 'group' => 'profile'],
            ['key' => 'profile_resume_pdf', 'value' => null, 'type' => 'file', 'group' => 'profile'],

            // Skills
            ['key' => 'skills_development', 'value' => 'Laravel,PHP,JavaScript,Vue.js,React,MySQL,PostgreSQL,Docker,Git,API Development', 'type' => 'textarea', 'group' => 'skills'],
            ['key' => 'skills_photography', 'value' => 'Landscape Photography,Long Exposure,Night Sky,Nature,Photo Editing,Lightroom,Photoshop', 'type' => 'textarea', 'group' => 'skills'],
            ['key' => 'skills_other', 'value' => 'UI/UX Design,Project Management,Technical Writing', 'type' => 'textarea', 'group' => 'skills'],

            // Additional Social Links
            ['key' => 'social_github', 'value' => '', 'type' => 'text', 'group' => 'social'],
            ['key' => 'social_linkedin', 'value' => '', 'type' => 'text', 'group' => 'social'],
            ['key' => 'social_behance', 'value' => '', 'type' => 'text', 'group' => 'social'],

            // Enhanced Contact
            ['key' => 'contact_whatsapp', 'value' => '', 'type' => 'text', 'group' => 'contact'],

            // Image Optimization
            ['key' => 'image_max_resolution', 'value' => '2048', 'type' => 'select', 'group' => 'optimization'],
            ['key' => 'image_quality', 'value' => '82', 'type' => 'number', 'group' => 'optimization'],

            // SEO Settings
            ['key' => 'seo_site_title', 'value' => '', 'type' => 'text', 'group' => 'seo'],
            ['key' => 'seo_site_description', 'value' => '', 'type' => 'textarea', 'group' => 'seo'],
            ['key' => 'seo_site_keywords', 'value' => '', 'type' => 'textarea', 'group' => 'seo'],
            ['key' => 'seo_og_image', 'value' => null, 'type' => 'image', 'group' => 'seo'],
            ['key' => 'seo_twitter_handle', 'value' => '', 'type' => 'text', 'group' => 'seo'],
            ['key' => 'seo_google_verification', 'value' => '', 'type' => 'text', 'group' => 'seo'],
            ['key' => 'seo_bing_verification', 'value' => '', 'type' => 'text', 'group' => 'seo'],
            ['key' => 'seo_google_analytics', 'value' => '', 'type' => 'text', 'group' => 'seo'],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
