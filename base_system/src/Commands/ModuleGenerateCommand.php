<?php

namespace BaseSystem\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ModuleGenerateCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'base-system:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a new module';

    protected $moduleContainer;

    protected $moduleSystem;

    protected string $moduleSystemPath;

    protected string $moduleName;

    protected string $moduleNamePlural;

    protected string $namespacePrefix;

    protected string $namespace;

    protected string $stubPath;

    protected string $modulePath;

    /**
     * @var array[]
     */
    protected array $moduleStubFiles = [
        'api.stub' => ['Routes', true, 'none'],
        'Controller.stub' => ['Controllers', true, 'prefixed'],
        'Filter.stub' => ['Filters', true, 'prefixed'],
        'CreateRequest.stub' => ['Requests', false, 'prefixed'],
        'Interface.stub' => ['Interfaces', false, 'prefixed'],
        'Model.stub' => ['Models', false, 'replaced'],
        'ServiceProvider.stub' => ['', false, 'none'],
        'Repository.stub' => ['Repositories', false, 'prefixed'],
        'UpdateRequest.stub' => ['Requests', false, 'prefixed'],
        'web.stub' => ['Routes', false, 'none'],
    ];

    /**
     * @var array[]
     */
    protected array $webModuleStubFiles = [
        'WebController.stub' => ['Controllers', true, 'prefixed', 'Controller.php'],
        'Filter.stub' => ['Filters', true, 'prefixed'],
        'WebCreateRequest.stub' => ['Requests', false, 'prefixed', 'CreateRequest.php'],
        'Interface.stub' => ['Interfaces', false, 'prefixed'],
        'Model.stub' => ['Models', false, 'replaced'],
        'ServiceProvider.stub' => ['', false, 'none'],
        'Repository.stub' => ['Repositories', false, 'prefixed'],
        'WebUpdateRequest.stub' => ['Requests', false, 'prefixed', 'UpdateRequest.php'],
        'WebRoute.stub' => ['Routes', false, 'none', 'web.php'],
    ];

    /**
     * @var array[]
     */
    protected array $systemStubFiles = [
        'SystemModel.stub' => ['Models', false, 'replaced'],
    ];

    /**
     * ModuleGenerateCommand constructor
     */
    public function __construct(protected Filesystem $files, protected Application $app)
    {
        parent::__construct();
        $this->moduleContainer = $this->app['config']->get('base-system.module');
        $this->moduleSystem = $this->app['config']->get('base-system.system');
        $this->namespacePrefix = '\App\\'.$this->moduleContainer;
        $this->stubPath = dirname(__DIR__).'/Stubs/';
    }

    /**
     * @throws FileNotFoundException
     */
    public function handle(): void
    {
        $this->makeModule();
    }

    /**
     * Generate the desired migration.
     *
     * @throws FileNotFoundException
     */
    protected function makeModule(): void
    {
        $name = ucfirst($this->argument('name'));
        $this->moduleName = Str::singular($name);
        $this->moduleNamePlural = Str::plural($name);
        $this->namespace = $this->namespacePrefix.'\\'.$this->moduleNamePlural;
        $this->modulePath = $this->getPath($this->moduleNamePlural, 'module');

        if ($this->files->exists($this->modulePath)) {
            $this->error('Module '.$name.'  already exists!');

            return;
        }
        $this->moduleSystemPath = $this->getPath($this->moduleName, 'system');
        $this->copyModuleStructureFromStubs();
        $this->info("Module $name successfully generated");
    }

    /**
     * Get the path to where we should store the migration.
     */
    protected function getPath(string $name, string $type): string
    {
        if ($type === 'module') {
            return app_path($this->moduleContainer.'/'.$name);
        }

        return app_path($this->moduleSystem.'/'.$name);
    }

    /**
     * Build the directory for the class if necessary.
     *
     * @throws FileNotFoundException
     */
    protected function copyModuleStructureFromStubs(): void
    {
        $this->copyModule();
        $this->copySystem();
    }

    /**
     * @throws FileNotFoundException
     */
    protected function copyModule(): void
    {
        if (! $this->files->isDirectory(dirname($this->modulePath))) {
            $this->files->makeDirectory(dirname($this->modulePath), 0777, true, true);
        }

        $this->files->copyDirectory(realpath($this->stubPath.'Module'), $this->modulePath);
        $this->generateModuleCodeFromStubs();
    }

    /**
     * Generate Cod from stubs
     *
     * @throws FileNotFoundException
     */
    protected function generateModuleCodeFromStubs(): void
    {
        $stubFiles = $this->option('web') ? $this->webModuleStubFiles : $this->moduleStubFiles;

        foreach ($stubFiles as $stubName => $stubConfig) {
            [$folderName, $isPlural, $action] = $stubConfig;
            $filenameOverride = $stubConfig[3] ?? null;

            $stubPath = $this->stubPath.$stubName;
            $destination = $this->modulePath.'/'.$folderName;
            $this->generateFileInDestination($stubPath, $isPlural, $stubName, $action, $destination, $filenameOverride);
        }
    }

    /**
     * @throws FileNotFoundException
     */
    protected function generateFileInDestination(string $stubPath, bool $isPlural, string $stubName, string $action, string $destination, ?string $filenameOverride = null): void
    {
        $stub = $this->files->get($stubPath);
        // replace the placeholders in the stubs
        $name = $isPlural ? $this->moduleNamePlural : $this->moduleName;
        $stub = str_replace(
            ['{{routemodule_singular}}', '{{routemodule}}', '{{module}}', '{module}', '{moduleContainer}', '{system}'],
            [strtolower($this->moduleName), strtolower($this->moduleNamePlural), $this->moduleNamePlural, $this->moduleName, $this->moduleContainer, $this->moduleSystem],
            $stub
        );
        $filename = $filenameOverride ?? str_replace('.stub', '.php', $stubName);
        switch ($action) {
            case 'prefixed':
                $filename = $name.$filename;
                break;
            case 'replaced':
                $filename = $name.'.php';
                break;
        }
        $this->files->put($destination.'/'.$filename, $stub);
    }

    /**
     * @throws FileNotFoundException
     */
    protected function copySystem(): void
    {
        if (! $this->files->isDirectory(dirname($this->moduleSystemPath))) {
            $this->files->makeDirectory(dirname($this->moduleSystemPath), 0777, true, true);
        }

        $this->files->copyDirectory(realpath($this->stubPath.'System'), $this->moduleSystemPath);
        $this->generateSystemCodeFromStubs();
    }

    /**
     * @throws FileNotFoundException
     */
    protected function generateSystemCodeFromStubs(): void
    {
        foreach ($this->systemStubFiles as $stubName => $stubConfig) {
            [$folderName, $isPlural, $action] = $stubConfig;

            $stubPath = $this->stubPath.$stubName;
            $destination = $this->moduleSystemPath.'/'.$folderName;
            $this->generateFileInDestination($stubPath, $isPlural, $stubName, $action, $destination, null);
        }
    }

    /**
     * @return array[]
     */
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the module'],
        ];
    }

    /**
     * @return array[]
     */
    protected function getOptions(): array
    {
        return [
            ['web', null, InputOption::VALUE_NONE, 'Generate a web (Inertia.js) module instead of an API module'],
        ];
    }
}
