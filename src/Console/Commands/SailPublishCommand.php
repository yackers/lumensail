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
     * Path of sail runtimes folder
     * @var string
     */
    protected string $runtimePath = 'vendor/laravel/sail/runtimes/';
    /**
     * Destination docker folder
     * @var string
     */
    protected string $dockerPath = 'docker/';

    /**
     * Publish laravel sail & docker files
     *
     * @return void
     */
    public function handle(): void
    {
        $this->laravel
            ->files
            ->copyDirectory(base_path($this->runtimePath), base_path($this->dockerPath));

        if ($this->confirm('Publish Laravel Sail bin file ?', true)) {

            $this->laravel
                ->files
                ->copy(base_path('vendor/laravel/sail/bin/sail'), base_path('sail'));

        }

        $this->laravel
            ->files
            ->replaceInFile($this->runtimePath, $this->dockerPath, base_path('docker-compose.yml'));
    }
}