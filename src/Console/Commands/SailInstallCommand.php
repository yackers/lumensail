<?php

namespace Yackers\LumenSail\Console\Commands;

use Illuminate\Console\Command;

class SailInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sail:install 
        {--with= : The services that should be included in the installation} 
        {--name= : Docker Service name}
        {--port= : APP_PORT to use by default}
        ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Laravel Sail\'s default Docker Compose file';

    /**
     *  Docker Service name used by default
     * @var string
     */
    protected string $defaultServiceName = 'laravel.test';
    /**
     * Laravel Sail path to stubs folder
     * @var string
     */
    protected string $stubsPath = 'vendor/laravel/sail/stubs/';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        if ($this->option('with')) {
            $services = $this->option('with') === 'none' ? [] : explode(',', $this->option('with'));
        } elseif ($this->option('no-interaction')) {
            $services = ['mysql', 'redis', 'selenium', 'mailhog'];
        } else {
            $services = $this->gatherServicesWithSymfonyMenu();
            if (in_array('none', $services, true)) { $services = []; }
        }

        $serviceName = $this->option('name') ?? $this->setDockerServiceName();
        $appPort = $this->option('port') ?? $this->setAppPort();

        $this->buildDockerCompose($services, $serviceName);
        $this->replaceEnvVariables($services, $appPort);
        $this->moveServerFile();

        if ($this->confirm('Publish the Laravel Sail Docker files ?', true)) {

            $this->call('sail:publish');
        }

        $this->info('Sail scaffolding installed successfully.');
    }

    protected function moveServerFile(): void
    {
        $this->laravel
            ->files
            ->copy(__DIR__ . '/../../../server.php', base_path('server.php')
            );
    }

    /**
     * Gather the desired Sail services using a Symfony menu.
     *
     * @return array
     */
    protected function gatherServicesWithSymfonyMenu(): array
    {
        return $this->choice('Which services would you like to install?', [
            'none',
            'mysql',
            'pgsql',
            'mariadb',
            'redis',
            'memcached',
            'meilisearch',
            'minio',
            'mailhog',
            'selenium',
        ], 0, null, true);
    }

    /**
     * Set another Docker Service Name instead of laravel.test.
     *
     * @return string
     */
    protected function setDockerServiceName(): string
    {
        return $this->ask('Do you want to use another Docker service name? (type in)', $this->defaultServiceName);
    }

    /**
     * Change default APP_PORT:80 to another one.
     * Used when there are multiple sites in same sail network
     *
     * @return string
     */
    protected function setAppPort(): string
    {
        return $this->ask('Do you want to change default app port? (type in)', '80');
    }

    /**
     * Build the Docker Compose file.
     *
     * @param  array  $services
     * @param  string  $serviceName
     * @return void
     */
    protected function buildDockerCompose(array $services, string $serviceName): void
    {
        $depends = collect($services)
            ->filter(fn($service) => in_array($service, ['mysql', 'pgsql', 'mariadb', 'redis', 'selenium']))
            ->map(fn($service) => "            - {$service}")
            ->whenNotEmpty(fn($collection) => $collection->prepend('depends_on:'))
            ->implode("\n");

        $stubs = rtrim(collect($services)
            ->map(fn($service) => file_get_contents(base_path($this->stubsPath . $service . '.stub')))
            ->implode(''));

        $volumes = collect($services)
            ->filter(fn($service) => in_array($service, ['mysql', 'pgsql', 'mariadb', 'redis', 'meilisearch', 'minio']))
            ->map(fn($service) => "    sail{$service}:\n        driver: local")
            ->whenNotEmpty(fn($collection) => $collection->prepend('volumes:'))
            ->implode("\n");

        $dockerCompose = file_get_contents(base_path($this->stubsPath . 'docker-compose.stub'));

        $dockerCompose = str_replace(
            ['{{depends}}', '{{services}}', '{{volumes}}'],
            [empty($depends) ? '' : '        '.$depends, $stubs, $volumes],
            $dockerCompose);

        // Set Docker Service Name
        if ($serviceName !== $this->defaultServiceName) {
            $dockerCompose = str_replace('laravel.test', $serviceName, $dockerCompose);
        }

        // Remove empty lines...
        $dockerCompose = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $dockerCompose);

        file_put_contents(base_path('docker-compose.yml'), $dockerCompose);
    }

    /**
     * Replace the Host environment variables in the app's .env file.
     *
     * @param  array  $services
     * @param string $appPort
     * @return void
     */
    protected function replaceEnvVariables(array $services, string $appPort): void
    {
        $environment = file_get_contents(base_path('.env'));

        if (in_array('pgsql', $services, true)) {
            $environment = str_replace('DB_CONNECTION=mysql', "DB_CONNECTION=pgsql", $environment);
            $environment = str_replace('DB_HOST=127.0.0.1', "DB_HOST=pgsql", $environment);
            $environment = str_replace('DB_PORT=3306', "DB_PORT=5432", $environment);
        } elseif (in_array('mariadb', $services, true)) {
            $environment = str_replace('DB_HOST=127.0.0.1', "DB_HOST=mariadb", $environment);
        } else {
            $environment = str_replace('DB_HOST=127.0.0.1', "DB_HOST=mysql", $environment);
        }

        $environment = str_replace('DB_USERNAME=root', "DB_USERNAME=sail", $environment);
        $environment = preg_replace("/DB_PASSWORD=(.*)/", "DB_PASSWORD=password", $environment);

        $environment = str_replace('MEMCACHED_HOST=127.0.0.1', 'MEMCACHED_HOST=memcached', $environment);
        $environment = str_replace('REDIS_HOST=127.0.0.1', 'REDIS_HOST=redis', $environment);

        if (in_array('meilisearch', $services, true)) {
            $environment .= "\nSCOUT_DRIVER=meilisearch";
            $environment .= "\nMEILISEARCH_HOST=http://meilisearch:7700\n";
        }

        if (str_contains($environment, "APP_PORT")) {
            $environment = preg_replace("/APP_PORT=(.*)/", "APP_PORT={$appPort}", $environment);
        } else {
            $environment .= "\nAPP_PORT={$appPort}\n";
        }

        file_put_contents(base_path('.env'), $environment);
    }
}
