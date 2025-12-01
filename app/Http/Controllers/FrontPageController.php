<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use App\Models\Setting;

class FrontPageController extends Controller
{
    /**
     * Display the front page / homepage with CV/Resume layout.
     */
    public function index()
    {
        // Get profile settings
        $profile = [
            'image' => Setting::get('profile_image'),
            'name' => Setting::get('profile_name', 'Your Name'),
            'title' => Setting::get('profile_title', 'Web Developer & Photographer'),
            'tagline' => Setting::get('profile_tagline'),
            'bio' => Setting::get('profile_bio'),
            'location' => Setting::get('profile_location'),
            'resume_pdf' => Setting::get('profile_resume_pdf'),
        ];

        // Get contact info
        $contact = [
            'email' => Setting::get('contact_email'),
            'phone' => Setting::get('contact_phone'),
            'whatsapp' => Setting::get('contact_whatsapp'),
            'location' => Setting::get('contact_location'),
        ];

        // Get social links
        $social = [
            'github' => Setting::get('social_github'),
            'linkedin' => Setting::get('social_linkedin'),
            'instagram' => Setting::get('social_instagram'),
            'twitter' => Setting::get('social_twitter'),
            'facebook' => Setting::get('social_facebook'),
            'youtube' => Setting::get('social_youtube'),
            'behance' => Setting::get('social_behance'),
            'flickr' => Setting::get('social_flickr'),
            '500px' => Setting::get('social_500px'),
        ];

        // Get skills
        $skills = [
            'development' => $this->parseSkills(Setting::get('skills_development', '')),
            'photography' => $this->parseSkills(Setting::get('skills_photography', '')),
            'other' => $this->parseSkills(Setting::get('skills_other', '')),
        ];

        // Get featured photos for the portfolio section
        $featuredPhotos = Photo::published()
            ->featured()
            ->with('category')
            ->latest('captured_at')
            ->take(6)
            ->get();

        // Get recent photos if no featured
        if ($featuredPhotos->isEmpty()) {
            $featuredPhotos = Photo::published()
                ->with('category')
                ->latest('created_at')
                ->take(6)
                ->get();
        }

        return view('front-page', compact('profile', 'contact', 'social', 'skills', 'featuredPhotos'));
    }

    /**
     * Parse comma-separated skills into array.
     */
    private function parseSkills(?string $skills): array
    {
        if (empty($skills)) {
            return [];
        }

        return array_filter(array_map('trim', explode(',', $skills)));
    }
}
