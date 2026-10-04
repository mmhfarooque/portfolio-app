<?php

namespace App\Services\Resume;

/**
 * Reads the private resume.json built by `resume:import`.
 */
class ResumeRepository
{
    private ?array $data = null;

    public function exists(): bool
    {
        return is_readable(config('resume.path'));
    }

    public function all(): ?array
    {
        if ($this->data !== null) {
            return $this->data;
        }

        if (! $this->exists()) {
            return null;
        }

        $decoded = json_decode(file_get_contents(config('resume.path')), true);

        return $this->data = is_array($decoded) ? $decoded : null;
    }

    /**
     * The only fields the locked page may show: name, headline and the
     * public contact email. Never the phone number or any experience.
     */
    public function lockedSummary(): array
    {
        $header = $this->all()['header'] ?? [];

        return [
            'name' => $header['name'] ?? config('app.name'),
            'headline' => $header['headline'] ?? null,
            'email' => config('resume.contact_email'),
        ];
    }
}
