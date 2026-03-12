<?php

namespace Xefi\LaravelOSDD\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[AsCommand(name: 'osdd:layer')]
class LayerCommand extends Command
{
    protected $name = 'osdd:layer';

    protected $description = 'Create a new OSDD layer';

    private const COMPONENTS = [
        'database/migrations',
        'database/factories',
        'database/seeders',
        'src/Models',
        'src/Factories',
        'src/Policies',
        'src/Providers',
    ];

    public function handle(): int
    {
        $name = $this->askForName();
        $targetPath = $this->askForTargetPath();
        $components = $this->askForComponents();

        $this->generate($name, $targetPath, $components);

        $this->components->info("Layer <options=bold>{$name}</> created at <options=bold>{$targetPath}</>.");

        return self::SUCCESS;
    }

    private function askForName(): string
    {
        return text(
            label: 'Layer name (vendor/package)',
            placeholder: 'acme/my-layer',
            required: true,
            validate: fn(string $value) => preg_match('/^[a-z0-9-]+\/[a-z0-9-]+$/', $value)
                ? null
                : 'Name must follow the vendor/package format using lowercase letters, numbers and hyphens.',
        );
    }

    private function askForTargetPath(): string
    {
        $paths = config('osdd.layers.paths');

        if (count($paths) === 1) {
            return reset($paths);
        }

        $chosen = select(
            label: 'Where should the layer be created?',
            options: $paths,
        );

        return $paths[$chosen];
    }

    private function askForComponents(): array
    {
        return multiselect(
            label: 'Which components should be scaffolded?',
            options: self::COMPONENTS,
            default: self::COMPONENTS,
            required: true,
        );
    }

    private function generate(string $name, string $targetPath, array $components): void
    {
        [$vendor, $package] = explode('/', $name);

        $layerPath = $targetPath . '/' . $package;
        $namespace = $this->toNamespace($vendor, $package);
        $serviceProviderClass = $this->toServiceProviderClass($package);

        $this->createFile(
            $layerPath . '/composer.json',
            $this->resolveStub('composer'),
            ['{{ name }}' => $name, '{{ namespace }}' => str_replace('\\', '\\\\', $namespace)],
        );

        foreach ($components as $component) {
            $componentPath = $layerPath . '/' . $component;

            mkdir($componentPath, recursive: true);

            if ($component === 'src/Providers') {
                $this->createFile(
                    $componentPath . '/' . $serviceProviderClass . '.php',
                    $this->resolveStub('service-provider'),
                    ['{{ namespace }}' => $namespace, '{{ class }}' => $serviceProviderClass],
                );
            } else {
                touch($componentPath . '/.gitkeep');
            }
        }
    }

    private function createFile(string $path, string $contents, array $replacements = []): void
    {
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, recursive: true);
        }

        file_put_contents($path, str_replace(array_keys($replacements), array_values($replacements), $contents));
    }

    private function resolveStub(string $stub): string
    {
        return file_get_contents(__DIR__ . '/../stubs/layer/' . $stub . '.stub');
    }

    private function toNamespace(string $vendor, string $package): string
    {
        return $this->toPascalCase($vendor) . '\\' . $this->toPascalCase($package);
    }

    private function toServiceProviderClass(string $package): string
    {
        return $this->toPascalCase($package) . 'ServiceProvider';
    }

    private function toPascalCase(string $value): string
    {
        return implode('', array_map(ucfirst(...), explode('-', $value)));
    }
}
