<?php

namespace App\Console\Commands;

use App\Services\Resume\ResumeMarkdownParser;
use Illuminate\Console\Command;
use RuntimeException;

class ImportResume extends Command
{
    protected $signature = 'resume:import
                            {--source= : Path to resume-master.md (defaults to config resume.source)}
                            {--output= : Path to write resume.json (defaults to config resume.path)}';

    protected $description = 'Rebuild the private resume.json from resume-master.md';

    public function handle(ResumeMarkdownParser $parser): int
    {
        $source = $this->option('source') ?: config('resume.source');
        $output = $this->option('output') ?: config('resume.path');

        if (! is_readable($source)) {
            $this->error("Source not found or not readable: {$source}");

            return self::FAILURE;
        }

        try {
            $data = $parser->parse(file_get_contents($source));
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        // Write atomically so a request never reads a half-written file,
        // and keep it readable by the app user only.
        $tmp = $output.'.tmp';
        file_put_contents($tmp, $json."\n");
        @chmod($tmp, 0600);
        rename($tmp, $output);

        $this->info(sprintf(
            'resume.json written: %d stats, %d highlights, %d skill groups, %d roles, %d AI cards, %d project groups, %d education entries, %d extra sections.',
            count($data['stats']),
            count($data['highlights']),
            count($data['skills']),
            count($data['experience']),
            count($data['aiShowcase']),
            count($data['projects']),
            count($data['education']),
            count($data['extraSections']),
        ));

        return self::SUCCESS;
    }
}
