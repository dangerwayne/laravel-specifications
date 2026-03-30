<?php

namespace DangerWayne\Specification\Console\Commands;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:specification')]
class SpecificationMakeCommand extends GeneratorCommand
{
    use CreatesMatchingTest;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:specification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new specification class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Specification';

    /**
     * Execute the console command.
     *
     * @return bool|null
     *
     * @throws FileNotFoundException
     */
    public function handle()
    {
        // First, generate the specification class
        $result = parent::handle();

        if ($result === false) {
            return false;
        }

        // Use the trait's method for test creation (only creates if --test or --pest options are used)
        $this->handleTestCreation($this->getPath($this->getNameInput()));

        return $result;
    }

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        // Determine which stub to use based on options
        if ($this->option('cacheable')) {
            return $this->resolveStubPath('/stubs/specification.cacheable.stub');
        }

        if ($this->option('composite')) {
            return $this->resolveStubPath('/stubs/specification.composite.stub');
        }

        if ($this->option('builder')) {
            return $this->resolveStubPath('/stubs/specification.builder.stub');
        }

        if ($this->option('model')) {
            return $this->resolveStubPath('/stubs/specification.model.stub');
        }

        return $this->resolveStubPath('/stubs/specification.stub');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     */
    protected function resolveStubPath(string $stub): string
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.'/..'.$stub;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\\Specifications';
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     *
     * @throws FileNotFoundException
     */
    protected function buildClass($name): string
    {
        $stub = parent::buildClass($name);

        $stub = $this->replaceModelReferences($stub);
        $stub = $this->replaceTypeSpecificCode($stub);

        return $stub;
    }

    /**
     * Replace the model references in the stub.
     */
    protected function replaceModelReferences(string $stub): string
    {
        if (! $this->option('model')) {
            return $stub;
        }

        $model = $this->option('model');

        // Handle model with namespace
        if (! Str::startsWith($model, '\\')) {
            $model = $this->qualifyModel($model);
        }

        $modelClass = class_basename($model);
        $modelVariable = Str::camel($modelClass);

        return str_replace(
            ['{{ namespacedModel }}', '{{ model }}', '{{ modelVariable }}'],
            [$model, $modelClass, $modelVariable],
            $stub
        );
    }

    /**
     * Replace type-specific code in the stub.
     */
    protected function replaceTypeSpecificCode(string $stub): string
    {
        $type = $this->option('type') ?? 'both';

        // Add type-specific imports or traits based on the specification type
        $imports = '';
        $traits = '';

        if ($type === 'query') {
            $imports = 'use Illuminate\Database\Eloquent\Builder;';
        } elseif ($type === 'collection') {
            $imports = 'use Illuminate\Support\Collection;';
        } else {
            $imports = "use Illuminate\Database\Eloquent\Builder;\nuse Illuminate\Support\Collection;";
        }

        return str_replace(
            ['{{ typeImports }}', '{{ typeTraits }}'],
            [$imports, $traits],
            $stub
        );
    }

    /**
     * Qualify the given model class base name.
     */
    protected function qualifyModel(string $model): string
    {
        $model = ltrim($model, '\\/');

        $model = str_replace('/', '\\', $model);

        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($model, $rootNamespace)) {
            return $model;
        }

        return is_dir(app_path('Models'))
            ? $rootNamespace.'Models\\'.$model
            : $rootNamespace.$model;
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     */
    protected function getPath($name): string
    {
        if ($this->option('inline')) {
            // Use flat structure without domain folders - strip any domain prefixes
            $className = class_basename($name);
            $name = $this->rootNamespace().'Specifications\\'.$className;
        }

        // Default behavior supports domain/module organization
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);

        return $this->laravel['path'].'/'.str_replace('\\', '/', $name).'.php';
    }

    /**
     * Get the console command arguments.
     */
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the specification'],
        ];
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the specification already exists'],
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'Generate a specification for the given model'],
            ['composite', 'c', InputOption::VALUE_NONE, 'Create a composite specification with example composition'],
            ['cacheable', null, InputOption::VALUE_NONE, 'Include the CacheableSpecification trait'],
            ['builder', 'b', InputOption::VALUE_NONE, 'Generate specification using the builder pattern'],
            ['test', null, InputOption::VALUE_NONE, 'Generate an accompanying PHPUnit test'],
            ['pest', null, InputOption::VALUE_NONE, 'Generate an accompanying Pest test'],
            ['type', null, InputOption::VALUE_OPTIONAL, 'The type of specification (query, collection, both)', 'both'],
            ['inline', null, InputOption::VALUE_NONE, 'Create specification without domain folder structure'],
        ];
    }
}
