@props(['content'])

@php
    // If content is null or empty, show nothing
    if (empty($content)) {
        return;
    }

    // Try to decode if it's JSON string
    if (is_string($content)) {
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // If not valid JSON, assume it's already HTML
            echo $content;
            return;
        }
    } else {
        $data = $content;
    }

    if (empty($data) || !isset($data['blocks'])) {
        return;
    }

    $html = '';

    foreach ($data['blocks'] as $block) {
        switch ($block['type'] ?? '') {
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
                    $html .= "<img src=\"{$url}\" alt=\"{$caption}\" class=\"rounded-lg\">";
                    if ($caption) {
                        $html .= "<figcaption>{$caption}</figcaption>";
                    }
                    $html .= '</figure>';
                }
                break;
        }
    }
@endphp

@if (!empty($html))
    {!! $html !!}
@endif
