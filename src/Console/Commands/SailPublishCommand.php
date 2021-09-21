<?php

namespace Yackers\LumenSail\Console\Commands;

use Illuminate\Console\Command;

class SailPublishCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sail:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish the Laravel Sail Docker files';

    /**
     * Versions to publish
     * @var array
     */
    protected array $versions = ['8.0', '7.4'];
    /**
     * Path of sail runtimes folder
     * @var string
     */
    protected string $runtimePath = './vendor/laravel/sail/runtimes/';
    /**
     * Destination docker folder
     * @var string
     */
    protected string $dockerPath = './docker/';

    /**
     * Publish laravel sail & docker files
     *
     * @return void
     */
    public function handle(): void
    {
        $this->laravel
            ->files
            ->copyDirectory(base_path('vendor/laravel/sail/runtimes'), base_path('docker')
        );

        $this->laravel
            ->files
            ->copy(base_path('vendor/laravel/sail/bin/sail'), base_path('sail')
        );

        $this->laravel->files->replaceInFile(
            array_map(fn($value): string => $this->runtimePath . $value, $this->versions),
            array_map(fn($value): string => $this->dockerPath . $value, $this->versions),
            base_path('docker-compose.yml')
        );
    }
}