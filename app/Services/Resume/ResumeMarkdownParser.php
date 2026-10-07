<?php

namespace App\Services\Resume;

use RuntimeException;

/**
 * Turns resume-master.md into the structured array behind /resume.
 *
 * The parser only splits text, it never rewords it: every sentence, number,
 * date and site name lands in the output exactly as written. The name,
 * headline, contact line and Experience are required and throw when they
 * drift, so `resume:import` fails loudly. Every other section is optional,
 * and a `##` section it does not know is passed through as plain paragraphs
 * and bullets instead of failing.
 */
class ResumeMarkdownParser
{
    /** Sections with their own place on the page. Anything else is "extra". */
    private const KNOWN_SECTIONS = [
        'profile', 'key achievements', 'core skills', 'experience', 'ai engineering',
        'selected projects and client range', 'freelance record', 'education and certification',
    ];

    /**
     * Headline numbers for the stats bar. Only the pattern lives here: the
     * number itself is read from the markdown, and a stat whose pattern
     * finds nothing is left out rather than shown with a typed-in value.
     */
    private const STATS = [
        ['key' => 'years', 'label' => 'years', 'pattern' => '/\b(?<n>\d+) years of delivery\b/i'],
        ['key' => 'sites', 'label' => 'sites', 'pattern' => '/\b(?<n>\d[\d,]*)(?<suffix>\+) client sites\b/i'],
        ['key' => 'woocommerce', 'label' => 'WooCommerce stores', 'pattern' => '/\b(?<n>\d+) WooCommerce stores\b/i'],
        ['key' => 'plugins', 'label' => 'plugins', 'pattern' => '/\b(?<n>\d+)(?: custom plugins\b|-plugin\b)/i'],
        ['key' => 'served', 'label' => 'sites served', 'prefix' => '~', 'pattern' => '/\babout (?<n>\d[\d,]*) (?:hosted )?sites\b/i'],
        ['key' => 'upwork', 'label' => 'on Upwork', 'pattern' => '/(?<prefix>\$)(?<n>\d+)(?<suffix>K\+) earned\b/'],
    ];

    /** Sector words that put a project under the Industrial filter. */
    private const INDUSTRIAL = '/industrial|manufactur|mining|materials handling|\bsteel\b|\biron\b|refuelling|engineering/i';

    /** @var array<string, string> section heading (lowercase) => body */
    private array $sections = [];

    /** @var array<string, string> section heading (lowercase) => heading as written */
    private array $titles = [];

    private string $preamble = '';

    public function parse(string $markdown): array
    {
        $markdown = str_replace(["\r\n", "\r"], "\n", $markdown);
        $this->splitSections($markdown);

        $data = [
            'header' => $this->header($markdown),
            'stats' => $this->stats($markdown),
            'profile' => $this->paragraphs($this->optional('profile')),
            'highlights' => array_map(fn ($b) => $this->segments($b), $this->bullets($this->optional('key achievements'))),
            'skills' => $this->skills($this->optional('core skills')),
            'experience' => $this->experience($this->section('experience')),
            'aiShowcase' => $this->aiShowcase($this->optional('ai engineering')),
        ];

        $projects = $this->projects($this->optional('selected projects and client range'));
        $data['projectsIntro'] = $projects['intro'];
        $data['projects'] = $projects['groups'];
        $data['clientRange'] = $projects['clientRange'];
        $data['freelance'] = implode("\n\n", $this->paragraphs($this->optional('freelance record')));

        $education = $this->education($this->optional('education and certification'));
        $data['education'] = $education['items'];
        $data['languages'] = $education['languages'];

        $data['extraSections'] = $this->extraSections();

        return $data;
    }

    // ------------------------------------------------------------------
    // Structure
    // ------------------------------------------------------------------

    private function splitSections(string $markdown): void
    {
        $this->sections = [];
        $this->titles = [];
        $parts = preg_split('/^## +(.+)$/m', $markdown, -1, PREG_SPLIT_DELIM_CAPTURE);

        $this->preamble = trim(array_shift($parts));

        for ($i = 0; $i < count($parts); $i += 2) {
            $key = strtolower(trim($parts[$i]));
            $this->sections[$key] = trim($parts[$i + 1] ?? '');
            $this->titles[$key] = trim($parts[$i]);
        }
    }

    private function section(string $name): string
    {
        if (! array_key_exists($name, $this->sections)) {
            throw new RuntimeException("resume-master.md is missing the \"## {$name}\" section (case-insensitive).");
        }

        return $this->sections[$name];
    }

    private function optional(string $name): string
    {
        return $this->sections[$name] ?? '';
    }

    /**
     * Any `##` section the page has no place for, kept as written.
     */
    private function extraSections(): array
    {
        $extra = [];

        foreach ($this->sections as $key => $body) {
            if (in_array($key, self::KNOWN_SECTIONS, true) || $body === '') {
                continue;
            }

            $extra[] = [
                'key' => trim(preg_replace('/[^a-z0-9]+/', '-', $key), '-'),
                'title' => $this->titles[$key],
                'paragraphs' => $this->paragraphs(preg_replace('/^- .*$/m', '', $body)),
                'bullets' => array_map(fn ($b) => $this->segments($b), $this->bullets($body)),
            ];
        }

        return $extra;
    }

    private function stats(string $markdown): array
    {
        $stats = [];

        foreach (self::STATS as $stat) {
            if (! preg_match($stat['pattern'], $markdown, $m)) {
                continue;
            }

            $prefix = ($stat['prefix'] ?? '').($m['prefix'] ?? '');
            $suffix = $m['suffix'] ?? '';

            $stats[] = [
                'key' => $stat['key'],
                'value' => (int) str_replace(',', '', $m['n']),
                'prefix' => $prefix,
                'suffix' => $suffix,
                'display' => $prefix.$m['n'].$suffix,
                'label' => $stat['label'],
            ];
        }

        return $stats;
    }

    // ------------------------------------------------------------------
    // Header
    // ------------------------------------------------------------------

    private function header(string $markdown): array
    {
        if (! preg_match('/^# +(.+)$/m', $this->preamble, $title)) {
            throw new RuntimeException('resume-master.md must start with a "# Name" title.');
        }

        // "Name — Detailed Resume" keeps only the name.
        $name = trim(preg_split('/\s+[—–-]\s+/u', trim($title[1]))[0]);

        $lines = array_values(array_filter(array_map('trim', explode("\n", $this->preamble))));
        $headline = '';
        $contactLine = '';

        foreach (array_slice($lines, 1) as $line) {
            if ($headline === '' && preg_match('/^\*\*(.+)\*\*$/', $line, $m)) {
                $headline = trim($m[1]);
            } elseif ($contactLine === '' && str_contains($line, '·')) {
                $contactLine = $line;
            }
        }

        if ($headline === '' || $contactLine === '') {
            throw new RuntimeException('resume-master.md needs a **headline** line and a contact line separated by ·');
        }

        $header = [
            'name' => $name,
            'headline' => $headline,
            'location' => null,
            'email' => null,
            'phone' => null,
            'linkedin' => null,
            'website' => null,
            'github' => null,
        ];

        foreach (array_map('trim', explode('·', $contactLine)) as $i => $part) {
            $link = $this->markdownLink($part);

            if ($link && str_contains($link['url'], 'linkedin.com')) {
                $header['linkedin'] = $link;
            } elseif ($link && str_contains($link['url'], 'github.com')) {
                $header['github'] = $link;
            } elseif ($link) {
                $header['website'] = $link;
            } elseif (filter_var($part, FILTER_VALIDATE_EMAIL)) {
                $header['email'] = $part;
            } elseif (preg_match('/^\+?[\d\s()-]{7,}/', $part)) {
                $digits = preg_replace('/[^\d+]/', '', preg_replace('/\(.*$/', '', $part));
                $header['phone'] = [
                    'label' => $part,
                    'tel' => $digits,
                    'whatsapp' => str_contains(strtolower($part), 'whatsapp') ? ltrim($digits, '+') : null,
                ];
            } elseif ($i === 0) {
                $header['location'] = $part;
            }
        }

        // GitHub is not on the contact line; take the first github.com link
        // anywhere in the document (the mfaruk.com project line).
        if ($header['github'] === null && preg_match('/\[([^\]]+)\]\((https?:\/\/(?:www\.)?github\.com\/[^)\s]+)\)/', $markdown, $m)) {
            $header['github'] = ['label' => $m[1], 'url' => $m[2]];
        }

        return $header;
    }

    // ------------------------------------------------------------------
    // Profile and skills
    // ------------------------------------------------------------------

    private function paragraphs(string $body): array
    {
        return array_values(array_filter(array_map(
            fn ($p) => trim(preg_replace('/\s*\n\s*/', ' ', $p)),
            preg_split('/\n\s*\n/', $body)
        )));
    }

    private function skills(string $body): array
    {
        $groups = [];

        foreach ($this->bullets($body) as $bullet) {
            if (! preg_match('/^\*\*(.+?):?\*\*:?\s*(.+)$/s', $bullet, $m)) {
                throw new RuntimeException("Skill line must look like \"- **Label:** items\": {$bullet}");
            }

            $groups[] = [
                'label' => rtrim(trim($m[1]), ':'),
                'text' => trim($m[2]),
                'items' => $this->skillItems(trim($m[2])),
            ];
        }

        return $groups;
    }

    /**
     * Splits a skills sentence into chips without rewording: sentences break
     * on ". " and "; ", and a sentence breaks further on commas only when
     * every piece stays short (a list), so claims stay whole.
     */
    private function skillItems(string $text): array
    {
        $items = [];

        foreach ($this->splitTopLevel($text, ['. ', '; ']) as $sentence) {
            $sentence = rtrim(trim($sentence), '.');
            if ($sentence === '') {
                continue;
            }

            $pieces = array_map('trim', $this->splitTopLevel($sentence, [', ']));
            $isList = count($pieces) > 1
                && max(array_map(fn ($p) => str_word_count($p), $pieces)) <= 6;

            foreach ($isList ? $pieces : [$sentence] as $piece) {
                if ($piece !== '') {
                    $items[] = $piece;
                }
            }
        }

        return $items;
    }

    // ------------------------------------------------------------------
    // Experience
    // ------------------------------------------------------------------

    private function experience(string $body): array
    {
        $roles = [];
        $blocks = preg_split('/^(?=\*\*[^*]+\*\* *·)/m', $body, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($blocks as $block) {
            $lines = explode("\n", trim($block));
            $headerLine = array_shift($lines);

            if (! preg_match('/^\*\*(.+?)\*\*\s*·\s*(.+?)\s*·\s*(.+)$/u', $headerLine, $m)) {
                throw new RuntimeException("Role line must look like \"**Role** · Company, Location · Start – End\": {$headerLine}");
            }

            [$company, $location] = array_pad(array_map('trim', explode(',', $m[2], 2)), 2, null);

            $dates = trim($m[3]);
            $concurrentNote = null;
            if (preg_match('/\(([^)]+)\)\s*$/', $dates, $note)) {
                $concurrentNote = trim($note[1]);
                $dates = trim(substr($dates, 0, -strlen($note[0])));
            }
            [$start, $end] = array_pad(array_map('trim', preg_split('/\s+[–-]\s+/u', $dates, 2)), 2, null);

            $rest = implode("\n", $lines);
            $context = [];
            foreach ($this->paragraphs(preg_replace('/^- .*$/m', '', $rest)) as $paragraph) {
                $context[] = $paragraph;
            }

            $roles[] = [
                'role' => trim($m[1]),
                'company' => $company,
                'location' => $location,
                'start' => $start,
                'end' => $end,
                'concurrent' => $concurrentNote !== null,
                'concurrentNote' => $concurrentNote,
                'context' => implode(' ', $context) ?: null,
                'bullets' => array_map(fn ($b) => $this->segments($b), $this->bullets($rest)),
            ];
        }

        if ($roles === []) {
            throw new RuntimeException('The Experience section has no roles.');
        }

        return $roles;
    }

    /**
     * The "## AI Engineering" section lists one card per line:
     *   - **Card title** – step → step → step (optional caption)
     * Steps split on "→"; a trailing (…) on the last step becomes the caption.
     */
    private function aiShowcase(string $body): array
    {
        $cards = [];

        foreach ($this->bullets($body) as $line) {
            if (! preg_match('/^\*\*(.+?)\*\*\s*[–—-]\s*(.+)$/u', $line, $m)) {
                throw new RuntimeException("AI Engineering line must look like \"- **Title** – step → step\": {$line}");
            }

            $steps = array_values(array_filter(array_map('trim', explode('→', $m[2])), fn ($s) => $s !== ''));
            $caption = null;

            $last = count($steps) - 1;
            if ($last >= 0 && preg_match('/^(.+?)\s*\(([^()]+)\)$/u', $steps[$last], $c)) {
                $steps[$last] = trim($c[1]);
                $caption = trim($c[2]);
            }

            $title = trim($m[1]);
            $cards[] = [
                'key' => trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($title)), '-'),
                'title' => $title,
                'steps' => count($steps) > 1 ? $steps : [],
                'body' => count($steps) > 1 ? null : ($steps[0] ?? null),
                'caption' => $caption,
            ];
        }

        return $cards;
    }

    // ------------------------------------------------------------------
    // Projects
    // ------------------------------------------------------------------

    private function projects(string $body): array
    {
        $chunks = preg_split('/^\*\*([^*\n]+)\*\*\s*$/m', $body, -1, PREG_SPLIT_DELIM_CAPTURE);
        $intro = implode(' ', $this->paragraphs(array_shift($chunks)));

        $groups = [];
        $clientRange = null;

        for ($i = 0; $i < count($chunks); $i += 2) {
            $title = trim($chunks[$i]);
            $content = trim($chunks[$i + 1] ?? '');

            // A trailing "Plus …" paragraph closes the section.
            if (preg_match('/\n\s*\n(Plus [^\n]+)\s*$/', $content, $m)) {
                $clientRange = trim($m[1]);
                $content = trim(substr($content, 0, -strlen($m[0])));
            }

            $key = $this->groupKey($title);
            $group = ['key' => $key, 'title' => $title, 'items' => [], 'also' => []];
            $bullets = $this->bullets($content);

            $table = $this->table($content);
            if ($table !== null) {
                $group['type'] = 'table';
                $group['columns'] = $table['columns'];
                foreach ($table['rows'] as $row) {
                    $item = $this->projectRow($row, $table['columns']);
                    $item['stack'] = $this->stack($key, $item['builder']);
                    $item['tags'] = $this->projectTags($key, $item['sector'].' '.$item['whatIDid']);
                    $group['items'][] = $item;
                }
            } elseif ($bullets !== [] && count(array_filter($bullets, fn ($b) => ! $this->isDomainList($b))) === 0) {
                // "- a.com.au, b.com.au" → one entry per domain.
                $group['type'] = 'sites';
                foreach ($bullets as $bullet) {
                    foreach (array_map('trim', explode(',', rtrim($bullet, '.'))) as $site) {
                        $group['items'][] = [
                            'sites' => [['site' => $site, 'url' => $this->siteUrl($site)]],
                            'stack' => $this->stack($key, null),
                            'tags' => $this->projectTags($key, ''),
                        ];
                    }
                }
            } else {
                $group['type'] = 'list';
                foreach ($bullets as $bullet) {
                    $group['items'][] = [
                        'segments' => $this->segments($bullet),
                        'tags' => $this->projectTags($key, $bullet),
                    ];
                }
            }

            if (preg_match('/^Also:\s*(.+)$/m', $content, $also)) {
                foreach (array_map('trim', explode(',', rtrim($also[1], '.'))) as $site) {
                    $group['also'][] = ['site' => $site, 'url' => $this->siteUrl($site)];
                }
            }

            $groups[] = $group;
        }

        return ['intro' => $intro, 'groups' => $groups, 'clientRange' => $clientRange];
    }

    private function groupKey(string $title): string
    {
        $t = strtolower($title);

        return match (true) {
            str_contains($t, 'government') => 'government',
            str_contains($t, 'multi-domain') => 'multiDomain',
            str_contains($t, 'shopify') => 'shopify',
            str_contains($t, 'woocommerce') => 'woocommerce',
            str_contains($t, 'e-commerce') => 'ecommerce',
            str_contains($t, 'agency') => 'agency',
            default => preg_replace('/[^a-z]+/', '-', $t),
        };
    }

    private function isDomainList(string $bullet): bool
    {
        return (bool) preg_match('/^(?:[a-z0-9-]+\.)+[a-z]{2,}(?:\s*,\s*(?:[a-z0-9-]+\.)+[a-z]{2,})*\.?$/i', trim($bullet));
    }

    /** Badges for a project card: the shop platform, then the builder cell. */
    private function stack(string $groupKey, ?string $builder): array
    {
        $stack = match ($groupKey) {
            'woocommerce', 'ecommerce' => ['WooCommerce'],
            'shopify' => ['Shopify'],
            default => [],
        };

        foreach (array_filter(array_map('trim', explode(',', (string) $builder))) as $part) {
            $stack[] = $part;
        }

        return array_values(array_unique($stack));
    }

    /** Filter-chip keys for a project: its group, plus Industrial and Own work by wording. */
    private function projectTags(string $groupKey, string $text): array
    {
        $tags = match ($groupKey) {
            'government' => ['government'],
            'multiDomain' => ['multiDomain'],
            'woocommerce', 'ecommerce' => ['woocommerce'],
            'shopify' => ['shopify'],
            default => [],
        };

        if (preg_match(self::INDUSTRIAL, $text)) {
            $tags[] = 'industrial';
        }
        if (preg_match('/\bown\b/i', $text)) {
            $tags[] = 'own';
        }

        return $tags;
    }

    private function projectRow(array $row, array $columns): array
    {
        $sitesCell = $row[0];
        $detail = $row[1] ?? '';

        $sites = [];
        foreach (array_map('trim', explode(',', $sitesCell)) as $site) {
            $sites[] = ['site' => $site, 'url' => $this->siteUrl($site)];
        }

        [$sector, $whatIDid] = $this->splitDetail($detail);

        return [
            'sites' => $sites,
            'sector' => $sector,
            'whatIDid' => $whatIDid,
            'builder' => count($columns) > 2 ? ($row[2] ?? null) : null,
        ];
    }

    /**
     * "Sector name. What I did." → sector + what I did.
     * "Sector name (what I did)" → the bracket holds what I did.
     * Otherwise the whole cell is the sector.
     */
    private function splitDetail(string $detail): array
    {
        $detail = trim($detail);

        if (preg_match('/^(.+?)\.\s+(.+)$/s', $detail, $m)) {
            return [trim($m[1]), trim($m[2])];
        }

        if (preg_match('/^(.+?)\s*\(([^)]+)\)$/', $detail, $m)) {
            return [trim($m[1]), trim($m[2])];
        }

        return [$detail, null];
    }

    // ------------------------------------------------------------------
    // Education
    // ------------------------------------------------------------------

    private function education(string $body): array
    {
        $items = [];
        foreach ($this->bullets($body) as $bullet) {
            $parts = array_map('trim', explode('·', $bullet));
            $items[] = [
                'qualification' => $parts[0] ?? $bullet,
                'institution' => $parts[1] ?? null,
                'years' => $parts[2] ?? null,
            ];
        }

        $languages = [];
        if (preg_match('/\*\*Languages:\*\*\s*(.+)$/m', $body, $m)) {
            $languages = array_map('trim', explode(',', $m[1]));
        }

        return ['items' => $items, 'languages' => $languages];
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function bullets(string $body): array
    {
        $bullets = [];
        foreach (explode("\n", $body) as $line) {
            if (preg_match('/^- (.+)$/', trim($line), $m)) {
                $bullets[] = trim($m[1]);
            }
        }

        return $bullets;
    }

    private function table(string $content): ?array
    {
        $lines = array_values(array_filter(
            array_map('trim', explode("\n", $content)),
            fn ($l) => str_starts_with($l, '|')
        ));

        if (count($lines) < 3) {
            return null;
        }

        $cells = fn ($line) => array_map('trim', explode('|', trim($line, '|')));

        return [
            'columns' => $cells($lines[0]),
            'rows' => array_map($cells, array_slice($lines, 2)),
        ];
    }

    private function markdownLink(string $text): ?array
    {
        if (preg_match('/^\[([^\]]+)\]\(([^)\s]+)\)$/', trim($text), $m)) {
            return ['label' => $m[1], 'url' => $m[2]];
        }

        return null;
    }

    /**
     * Inline text → segments the page can render without v-html:
     * plain text, markdown links and bare AU/NZ client domains (site.com.au)
     * as links. Bare .com/.net are left as text so product names that
     * happen to look like domains are not turned into links.
     */
    private function segments(string $text): array
    {
        $pattern = '/\[([^\]]+)\]\(([^)\s]+)\)|\b((?:[a-z0-9-]+\.)+(?:com\.au|org\.au|edu\.au|gov\.au|net\.au|co\.nz))\b/i';

        $segments = [];
        $offset = 0;

        preg_match_all($pattern, $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        foreach ($matches as $match) {
            [$whole, $start] = $match[0];

            if ($start > $offset) {
                $segments[] = ['text' => substr($text, $offset, $start - $offset)];
            }

            if (! empty($match[1][0])) {
                $segments[] = ['text' => $match[1][0], 'url' => $match[2][0]];
            } else {
                $domain = $match[3][0];
                $segments[] = ['text' => $domain, 'url' => $this->siteUrl($domain)];
            }

            $offset = $start + strlen($whole);
        }

        if ($offset < strlen($text)) {
            $segments[] = ['text' => substr($text, $offset)];
        }

        return $segments;
    }

    private function siteUrl(string $site): string
    {
        return 'https://'.trim($site);
    }

    /**
     * Splits on any of $separators, but never inside (…) brackets.
     */
    private function splitTopLevel(string $text, array $separators): array
    {
        $parts = [];
        $depth = 0;
        $current = '';
        $length = strlen($text);

        for ($i = 0; $i < $length; $i++) {
            $char = $text[$i];
            $depth += $char === '(' ? 1 : ($char === ')' ? -1 : 0);

            if ($depth === 0) {
                foreach ($separators as $separator) {
                    if (substr($text, $i, strlen($separator)) === $separator) {
                        $parts[] = $current;
                        $current = '';
                        $i += strlen($separator) - 1;
                        continue 2;
                    }
                }
            }

            $current .= $char;
        }

        $parts[] = $current;

        return $parts;
    }
}
