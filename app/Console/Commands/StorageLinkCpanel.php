<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class StorageLinkCpanel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:link-cpanel {public_path? : The path to public_html directory}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create storage symlink for cPanel split installation';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $storagePath = storage_path('app/public');

        // Determine public path
        $publicPath = $this->argument('public_path');

        if (!$publicPath) {
            // Try to auto-detect public_html
            $homeDir = dirname(dirname(base_path()));
            $possiblePaths = [
                $homeDir . '/public_html/storage',
                base_path('public/storage'),
            ];

            foreach ($possiblePaths as $path) {
                $dir = dirname($path);
                if (is_dir($dir)) {
                    $publicPath = $path;
                    break;
                }
            }
        } else {
            $publicPath = rtrim($publicPath, '/') . '/storage';
        }

        if (!$publicPath) {
            $this->error('Could not determine public path. Please provide it as an argument.');
            return Command::FAILURE;
        }

        // Remove existing symlink or directory
        if (is_link($publicPath)) {
            unlink($publicPath);
            $this->info("Removed existing symlink at [$publicPath]");
        } elseif (is_dir($publicPath)) {
            $this->warn("Directory exists at [$publicPath]. Please remove it manually.");
            return Command::FAILURE;
        }

        // Create symlink
        if (symlink($storagePath, $publicPath)) {
            $this->info("Symlink created: [$publicPath] -> [$storagePath]");
            return Command::SUCCESS;
        }

        $this->error("Failed to create symlink. You may need to create it manually:");
        $this->line("ln -s $storagePath $publicPath");
        return Command::FAILURE;
    }
}
