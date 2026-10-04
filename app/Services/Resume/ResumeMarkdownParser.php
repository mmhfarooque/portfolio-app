<?php

namespace App\Services\Resume;

use RuntimeException;

/**
 * Turns resume-master.md into the structured array behind /resume.
 *
 * The parser only splits text, it never rewords it: every sentence, number,
 * date and site name lands in the output exactly as written. When the
 * markdown drifts from the expected shape it throws instead of guessing, so
 * `resume:import` fails loudly rather than publishing a half-empty page.
 */
class ResumeMarkdownParser
{
    /** @var array<string, string> section heading (lowercase) => body */
    private array $sections = [];

    private string $preamble = '';

    public function parse(string $markdown): array
    {
        $markdown = str_replace(["\r\n", "\r"], "\n", $markdown);
        $this->splitSections($markdown);

        $experience = $this->experience($this->section('experience'));

        $data = [
            'header' => $this->header($markdown),
            'profile' => $this->paragraphs($this->section('profile')),
            'skills' => $this->skills($this->section('core skills')),
            'experience' => $experience,
            'aiShowcase' => $this->aiShowcase($this->section('ai engineering'), $experience),
        ];

        $projects = $this->projects($this->section('selected projects and client range'));
        $data['projectsIntro'] = $projects['intro'];
        $data['projects'] = $projects['groups'];
        $data['clientRange'] = $projects['clientRange'];
        $data['freelance'] = implode("\n\n", $this->paragraphs($this->section('freelance record')));

        $education = $this->education($this->section('education and certification'));
        $data['education'] = $education['items'];
        $data['languages'] = $education['languages'];

        return $data;
    }

    // ------------------------------------------------------------------
    // Structure
    // ------------------------------------------------------------------

    private function splitSections(string $markdown): void
    {
        $this->sections = [];
        $parts = preg_split('/^## +(.+)$/m', $markdown, -1, PREG_SPLIT_DELIM_CAPTURE);

        $this->preamble = trim(array_shift($parts));

        for ($i = 0; $i < count($parts); $i += 2) {
            $this->sections[strtolower(trim($parts[$i]))] = trim($parts[$i + 1] ?? '');
        }
    }

    private function section(string $name): string
    {
        if (! array_key_exists($name, $this->sections)) {
            throw new RuntimeException("resume-master.md is missing the \"## {$name}\" section (case-insensitive).");
        }

        return $this->sections[$name];
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
     *   - **Card title** · from: Opening phrase of an Experience sentence · steps
     * The card body is that Experience sentence, verbatim. "steps" draws it
     * as a step diagram. Titles and phrases live in resume-master.md only.
     */
    private function aiShowcase(string $body, array $experience): array
    {
        $sentences = [];
        foreach ($experience as $role) {
            foreach ($role['bullets'] as $bullet) {
                foreach ($this->sentences($this->plain($bullet)) as $sentence) {
                    $sentences[] = $sentence;
                }
            }
        }

        $cards = [];
        foreach ($this->bullets($body) as $line) {
            if (! preg_match('/^\*\*(.+?)\*\*\s*·\s*from:\s*(.+?)(?:\s*·\s*(steps))?$/u', $line, $m)) {
                throw new RuntimeException("AI Engineering line must look like \"- **Title** · from: Opening phrase [· steps]\": {$line}");
            }

            [$title, $starts, $withSteps] = [trim($m[1]), trim($m[2]), ! empty($m[3])];

            $found = null;
            foreach ($sentences as $sentence) {
                if (str_starts_with($sentence, $starts)) {
                    $found = $sentence;
                    break;
                }
            }

            if ($found === null) {
                throw new RuntimeException("AI Engineering card \"{$title}\" needs an Experience sentence starting \"{$starts}\".");
            }

            $cards[] = [
                'key' => trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($title)), '-'),
                'title' => $title,
                'body' => $found,
                'steps' => $withSteps ? $this->pipelineSteps($found) : [],
            ];
        }

        if ($cards === []) {
            throw new RuntimeException('The AI Engineering section has no cards.');
        }

        return $cards;
    }

    /**
     * "Built a pipeline: it does A, does B, does C, and, when approved, does D."
     * → four verbatim clauses (A, B, C, "when approved, does D") for the
     * step diagram.
     */
    private function pipelineSteps(string $sentence): array
    {
        $after = trim(substr($sentence, (strpos($sentence, ':') ?: -1) + 1));
        $parts = array_map('trim', explode(', ', rtrim($after, '.')));

        if (count($parts) < 4) {
            return [];
        }

        $steps = array_slice($parts, 0, 3);
        $steps[] = preg_replace('/^and,?\s*/', '', implode(', ', array_slice($parts, 3)));
        $steps[0] = preg_replace('/^it\s+/', '', $steps[0]);

        return array_map(fn ($s) => mb_strtoupper(mb_substr($s, 0, 1)).mb_substr($s, 1), $steps);
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

            $group = ['key' => $this->groupKey($title), 'title' => $title, 'items' => [], 'also' => []];

            $table = $this->table($content);
            if ($table !== null) {
                $group['type'] = 'table';
                $group['columns'] = $table['columns'];
                foreach ($table['rows'] as $row) {
                    $group['items'][] = $this->projectRow($row, $table['columns']);
                }
            } else {
                $group['type'] = 'list';
                foreach ($this->bullets($content) as $bullet) {
                    $group['items'][] = ['segments' => $this->segments($bullet)];
                }
            }

            if (preg_match('/^Also:\s*(.+)$/m', $content, $also)) {
                foreach (array_map('trim', explode(',', rtrim($also[1], '.'))) as $site) {
                    $group['also'][] = ['site' => $site, 'url' => $this->siteUrl($site)];
                }
            }

            $groups[] = $group;
        }

        if ($clientRange === null) {
            throw new RuntimeException('Selected projects needs a closing "Plus …" client range line.');
        }

        return ['intro' => $intro, 'groups' => $groups, 'clientRange' => $clientRange];
    }

    private function groupKey(string $title): string
    {
        $t = strtolower($title);

        return match (true) {
            str_contains($t, 'government') => 'government',
            str_contains($t, 'multi-domain') => 'multiDomain',
            str_contains($t, 'e-commerce') => 'ecommerce',
            str_contains($t, 'agency') => 'agency',
            default => preg_replace('/[^a-z]+/', '-', $t),
        };
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

    private function plain(array $segments): string
    {
        return implode('', array_column($segments, 'text'));
    }

    private function sentences(string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/(?<=\.)\s+(?=[A-Z])/', $text))));
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
