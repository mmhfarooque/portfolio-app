<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\LoggingService;
use Illuminate\Http\Request;

class AboutController extends Controller
{
    /**
     * Show the GrapesJS editor for the About page.
     */
    public function editor()
    {
        $content = Setting::get('about_content', $this->getDefaultContent());

        return view('admin.about.editor', compact('content'));
    }

    /**
     * Show the Editor.js editor for the About page or Profile bio.
     */
    public function editorJs(Request $request)
    {
        $target = $request->query('target', 'about');
        $editorData = null;

        if ($target === 'profile') {
            $editorData = Setting::get('profile_bio');
        } else {
            $editorData = Setting::get('about_editorjs_data');
        }

        if ($editorData) {
            $editorData = json_decode($editorData, true);
        }

        return view('admin.about.editorjs', compact('editorData', 'target'));
    }

    /**
     * Save the About page content (GrapesJS).
     */
    public function save(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $this->saveSetting('about_content', $request->content, 'html');

        LoggingService::activity('about.content_updated', 'About page content updated via GrapesJS editor');

        return response()->json([
            'success' => true,
            'message' => 'Content saved successfully',
        ]);
    }

    /**
     * Save the About page content (Editor.js).
     */
    public function saveEditorJs(Request $request)
    {
        $request->validate([
            'content' => 'required|array',
            'target' => 'nullable|string|in:about,profile',
        ]);

        $target = $request->input('target', 'about');

        if ($target === 'profile') {
            // Save raw Editor.js data for profile bio
            $this->saveSetting('profile_bio', json_encode($request->content), 'editorjs', 'profile');

            LoggingService::activity('profile.bio_updated', 'Profile bio content updated via Editor.js');
        } else {
            // Save raw Editor.js data for about page editing
            $this->saveSetting('about_editorjs_data', json_encode($request->content), 'json', 'about');

            // Convert to HTML for display
            $html = $this->editorJsToHtml($request->content);
            $this->saveSetting('about_content', $html, 'html', 'about');

            LoggingService::activity('about.content_updated', 'About page content updated via Editor.js');
        }

        return response()->json([
            'success' => true,
            'message' => 'Content saved successfully',
        ]);
    }

    /**
     * Convert Editor.js blocks to HTML.
     */
    protected function editorJsToHtml(array $data): string
    {
        $html = '<div class="editorjs-content">';

        foreach ($data['blocks'] ?? [] as $block) {
            switch ($block['type']) {
                case 'header':
                    $level = $block['data']['level'] ?? 2;
                    $text = $block['data']['text'] ?? '';
                    $html .= "<h{$level}>{$text}</h{$level}>";
                    break;

                case 'paragraph':
                    $text = $block['data']['text'] ?? '';
                    $html .= "<p>{$text}</p>";
                    break;

                case 'list':
                    $style = $block['data']['style'] ?? 'unordered';
                    $tag = $style === 'ordered' ? 'ol' : 'ul';
                    $items = $block['data']['items'] ?? [];
                    $html .= "<{$tag}>";
                    foreach ($items as $item) {
                        $html .= "<li>{$item}</li>";
                    }
                    $html .= "</{$tag}>";
                    break;

                case 'quote':
                    $text = $block['data']['text'] ?? '';
                    $caption = $block['data']['caption'] ?? '';
                    $html .= '<blockquote>';
                    $html .= "<p>{$text}</p>";
                    if ($caption) {
                        $html .= "<footer>— {$caption}</footer>";
                    }
                    $html .= '</blockquote>';
                    break;

                case 'delimiter':
                    $html .= '<hr>';
                    break;

                case 'image':
                    $url = $block['data']['file']['url'] ?? $block['data']['url'] ?? '';
                    $caption = $block['data']['caption'] ?? '';
                    if ($url) {
                        $html .= '<figure>';
                        $html .= "<img src=\"{$url}\" alt=\"{$caption}\">";
                        if ($caption) {
                            $html .= "<figcaption>{$caption}</figcaption>";
                        }
                        $html .= '</figure>';
                    }
                    break;
            }
        }

        $html .= '</div>';

        // Theme-aware styling - inherits from page theme
        $styles = <<<CSS
<style>
.editorjs-content {
    max-width: 100%;
}
.editorjs-content h1,
.editorjs-content h2,
.editorjs-content h3 {
    color: inherit;
}
.editorjs-content h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
}
.editorjs-content h2 {
    font-size: 1.75rem;
    font-weight: 600;
    margin-top: 2rem;
    margin-bottom: 1rem;
}
.editorjs-content h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
}
.editorjs-content p,
.editorjs-content li {
    font-size: 1.125rem;
    line-height: 1.8;
    opacity: 0.85;
}
.editorjs-content p {
    margin-bottom: 1rem;
}
.editorjs-content blockquote {
    border-left: 4px solid currentColor;
    padding: 1.5rem 2rem;
    margin: 2rem 0;
    border-radius: 0 8px 8px 0;
    background: rgba(255, 255, 255, 0.05);
}
.editorjs-content blockquote p {
    font-size: 1.25rem;
    font-style: italic;
    margin-bottom: 0.5rem;
    opacity: 1;
}
.editorjs-content blockquote footer {
    font-size: 0.875rem;
    opacity: 0.7;
}
.editorjs-content ul, .editorjs-content ol {
    margin: 1rem 0;
    padding-left: 1.5rem;
}
.editorjs-content li {
    margin-bottom: 0.5rem;
}
.editorjs-content hr {
    border: none;
    height: 1px;
    margin: 2rem 0;
    background: currentColor;
    opacity: 0.2;
}
.editorjs-content figure {
    margin: 2rem 0;
}
.editorjs-content figure img {
    width: 100%;
    border-radius: 8px;
}
.editorjs-content figcaption {
    text-align: center;
    font-size: 0.875rem;
    margin-top: 0.5rem;
    opacity: 0.7;
}
.editorjs-content a {
    text-decoration: underline;
}
</style>
CSS;

        return $styles . $html;
    }

    /**
     * Helper to save a setting.
     */
    protected function saveSetting(string $key, string $value, string $type, string $group = 'about'): void
    {
        $setting = Setting::where('key', $key)->first();

        if ($setting) {
            $setting->update(['value' => $value]);
        } else {
            Setting::create([
                'key' => $key,
                'value' => $value,
                'type' => $type,
                'group' => $group,
            ]);
        }

        Setting::clearCache();
    }

    /**
     * Get default content for new About pages.
     */
    protected function getDefaultContent(): string
    {
        return <<<HTML
<section style="padding: 60px 20px; max-width: 900px; margin: 0 auto;">
    <div style="display: flex; gap: 40px; align-items: center; flex-wrap: wrap; margin-bottom: 40px;">
        <img src="https://via.placeholder.com/250x250" alt="Profile Photo" style="width: 250px; height: 250px; border-radius: 50%; object-fit: cover; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
        <div style="flex: 1; min-width: 280px;">
            <h1 style="font-size: 36px; font-weight: 700; margin: 0 0 10px; color: #1f2937;">Hello, I'm [Your Name]</h1>
            <p style="font-size: 18px; color: #6b7280; margin: 0 0 20px;">Photographer & Visual Storyteller</p>
            <p style="font-size: 16px; line-height: 1.7; color: #4b5563;">
                I'm a passionate photographer based in [Your Location]. I specialize in landscape, nature, and portrait photography, capturing moments that tell compelling stories.
            </p>
        </div>
    </div>
</section>
HTML;
    }
}
