<?php

namespace Sirj3x\Websocket\Console\Commands;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputOption;

class EventMakeCommand extends GeneratorCommand
{
    use CreatesMatchingTest;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:ws-event';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new event class for websocket';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Event';

    /**
     * Request class name
     *
     * @var string
     */
    protected string $requestClassName = '';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('requests')) {
            $stub = DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'event_with_request.invokable.stub';
        } else {
            $stub = DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'event.invokable.stub';
        }

        return $this->resolveStubPath($stub);
    }

    /**
     * Get the parent stub file for the generator.
     *
     * @return string
     */
    protected function getParentStub()
    {
        $stub = DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'event.parent.stub';

        return $this->resolveStubPath($stub);
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param string $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return $this->laravel->basePath('vendor' . DIRECTORY_SEPARATOR . 'sirjex' . DIRECTORY_SEPARATOR . 'websocket' . $stub);
    }

    /**
     * Build the parent class with the given name.
     *
     * @param string $name
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildParentClass(string $name = 'parent')
    {
        $stub = $this->files->get($this->getParentStub());

        return $this->replaceNamespace($stub, $name)->replaceClass($stub, $name);
    }

    /**
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return 'App\\Websocket\\Events';
    }

    /**
     * Build the parent class for all events
     *
     */
    protected function buildParentClassFinally()
    {
        if (!file_exists(app_path('Websocket/Events/Event.php'))) {
            Filesystem::put(app_path('Websocket/Events/Event.php'), $this->sortImports($this->buildParentClass()));
        }
    }

    /**
     * Execute the console command.
     *
     * @return bool|null
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle()
    {
        // First we need to ensure that the given name is not a reserved word within the PHP
        // language and that the class name will actually be valid. If it is not valid we
        // can error now and prevent from polluting the filesystem using invalid files.
        if ($this->isReservedName($this->getNameInput())) {
            $this->error('The name "' . $this->getNameInput() . '" is reserved by PHP.');

            return false;
        }

        $name = $this->qualifyClass($this->getNameInput());

        $path = $this->getPath($name);

        // Next, We will check to see if the class already exists. If it does, we don't want
        // to create the class and overwrite the user's code. So, we will bail out so the
        // code is untouched. Otherwise, we will continue generating this class' files.
        if ((!$this->hasOption('force') ||
                !$this->option('force')) &&
            $this->alreadyExists($this->getNameInput())) {
            $this->error($this->type . ' already exists!');

            return false;
        }

        if ($this->option('requests')) {
            $this->call('make:ws-request', [
                'name' => $this->getNameInput(true),
            ]);
        }

        // Next, we will generate the path to the location where this class' file should get
        // written. Then, we will build the class and make the proper replacements on the
        // stub files so that it gets the correctly formatted namespace and class name.
        $this->makeDirectory($path);

        $this->files->put($path, $this->sortImports($this->buildClass($name)));

        $this->info($this->type . ' created successfully.');

        $this->buildParentClassFinally();

        if (in_array(CreatesMatchingTest::class, class_uses_recursive($this))) {
            $this->handleTestCreation($path);
        }
    }

    /**
     * Build the class with the given name.
     *
     * Remove the base event import if we are already in the base namespace.
     *
     * @param string $name
     * @return string
     */
    protected function buildClass($name)
    {
        $eventNamespace = $this->getNamespace($name);
        $replace = [];

        if ($this->option('parent')) {
            $replace = $this->buildParentReplacements();
        }

        if ($this->option('model')) {
            $replace = $this->buildModelReplacements($replace);
        }

        $replace["use {$eventNamespace}\Event;\n"] = '';

        return str_replace(
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );
    }

    /**
     * Build the replacements for a parent event.
     *
     * @return array
     */
    protected function buildParentReplacements()
    {
        $parentModelClass = $this->parseModel($this->option('parent'));

        if (!class_exists($parentModelClass)) {
            if ($this->confirm("A {$parentModelClass} model does not exist. Do you want to generate it?", true)) {
                $this->call('make:model', ['name' => $parentModelClass]);
            }
        }

        return [
            'ParentDummyFullModelClass' => $parentModelClass,
            '{{ namespacedParentModel }}' => $parentModelClass,
            '{{namespacedParentModel}}' => $parentModelClass,
            'ParentDummyModelClass' => class_basename($parentModelClass),
            '{{ parentModel }}' => class_basename($parentModelClass),
            '{{parentModel}}' => class_basename($parentModelClass),
            'ParentDummyModelVariable' => lcfirst(class_basename($parentModelClass)),
            '{{ parentModelVariable }}' => lcfirst(class_basename($parentModelClass)),
            '{{parentModelVariable}}' => lcfirst(class_basename($parentModelClass)),
        ];
    }

    /**
     * Build the model replacement values.
     *
     * @param array $replace
     * @return array
     */
    protected function buildModelReplacements(array $replace)
    {
        $modelClass = $this->parseModel($this->option('model'));

        /*if (!class_exists($modelClass)) {
            if ($this->confirm("A {$modelClass} model does not exist. Do you want to generate it?", true)) {
                $this->call('make:model', ['name' => $modelClass]);
            }
        }*/

        $replace = $this->buildFormRequestReplacements($replace, $modelClass);

        return array_merge($replace, [
            'DummyFullModelClass' => $modelClass,
            '{{ namespacedModel }}' => $modelClass,
            '{{namespacedModel}}' => $modelClass,
            'DummyModelClass' => class_basename($modelClass),
            '{{ model }}' => class_basename($modelClass),
            '{{model}}' => class_basename($modelClass),
            'DummyModelVariable' => lcfirst(class_basename($modelClass)),
            '{{ modelVariable }}' => lcfirst(class_basename($modelClass)),
            '{{modelVariable}}' => lcfirst(class_basename($modelClass)),
        ]);
    }

    /**
     * Get the fully-qualified model class name.
     *
     * @param string $model
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function parseModel($model)
    {
        if (preg_match('([^A-Za-z0-9_/\\\\])', $model)) {
            throw new InvalidArgumentException('Model name contains invalid characters.');
        }

        return $this->qualifyModel($model);
    }

    /**
     * Build the model replacement values.
     *
     * @param array $replace
     * @param string $modelClass
     * @return array
     */
    protected function buildFormRequestReplacements(array $replace, $modelClass)
    {
        [$namespace, $storeRequestClass, $updateRequestClass] = [
            'Illuminate\\Http', 'Request', 'Request',
        ];

        if ($this->option('requests')) {
            $namespace = 'App\\Http\\Requests';

            [$storeRequestClass, $updateRequestClass] = $this->generateFormRequests(
                $modelClass, $storeRequestClass, $updateRequestClass
            );
        }

        $namespacedRequests = $namespace . '\\' . $storeRequestClass . ';';

        if ($storeRequestClass !== $updateRequestClass) {
            $namespacedRequests .= PHP_EOL . 'use ' . $namespace . '\\' . $updateRequestClass . ';';
        }

        return array_merge($replace, [
            '{{ storeRequest }}' => $storeRequestClass,
            '{{storeRequest}}' => $storeRequestClass,
            '{{ updateRequest }}' => $updateRequestClass,
            '{{updateRequest}}' => $updateRequestClass,
            '{{ namespacedStoreRequest }}' => $namespace . '\\' . $storeRequestClass,
            '{{namespacedStoreRequest}}' => $namespace . '\\' . $storeRequestClass,
            '{{ namespacedUpdateRequest }}' => $namespace . '\\' . $updateRequestClass,
            '{{namespacedUpdateRequest}}' => $namespace . '\\' . $updateRequestClass,
            '{{ namespacedRequests }}' => $namespacedRequests,
            '{{namespacedRequests}}' => $namespacedRequests,
        ]);
    }

    /**
     * Generate the form requests for the given model and classes.
     *
     * @param string $modelName
     * @param string $storeRequestClass
     * @param string $updateRequestClass
     * @return array
     */
    protected function generateFormRequests($modelClass, $storeRequestClass, $updateRequestClass)
    {
        $storeRequestClass = 'Store' . class_basename($modelClass) . 'Request';

        $this->call('make:request', [
            'name' => $storeRequestClass,
        ]);

        $updateRequestClass = 'Update' . class_basename($modelClass) . 'Request';

        $this->call('make:request', [
            'name' => $updateRequestClass,
        ]);

        return [$storeRequestClass, $updateRequestClass];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['api', null, InputOption::VALUE_NONE, 'Exclude the create and edit methods from the event.'],
            ['type', null, InputOption::VALUE_REQUIRED, 'Manually specify the event stub file to use.'],
            ['force', null, InputOption::VALUE_NONE, 'Create the class even if the event already exists'],
            ['invokable', 'i', InputOption::VALUE_NONE, 'Generate a single method, invokable event class.'],
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'Generate a resource event for the given model.'],
            /*['resource', 'r', InputOption::VALUE_NONE, 'Generate a resource event class.'],
            ['requests', 'R', InputOption::VALUE_NONE, 'Generate FormRequest classes for store and update.'],*/
            ['parent', 'p', InputOption::VALUE_OPTIONAL, 'Generate a nested resource event class.'],
            ['requests', 'r', InputOption::VALUE_NONE, 'Generate FormRequest classes for store and update.'],
        ];
    }

    protected function getNameInput($forRequest = false)
    {
        if ($forRequest) {
            return trim($this->argument('name')) . 'Request';
        } else {
            return trim($this->argument('name')) . 'Event';
        }
    }

    /**
     * Replace the namespace for the given stub.
     *
     * @param string $stub
     * @param string $name
     * @return $this
     */
    protected function replaceNamespace(&$stub, $name)
    {
        if ($this->option('requests')) {
            $searches = [
                ['DummyNamespace', 'DummyRootNamespace', 'NamespacedDummyUserModel', 'DummyRequestClass', 'DummyUseRequestClass'],
                ['{{ namespace }}', '{{ rootNamespace }}', '{{ namespacedUserModel }}', '{{ requestClass }}', '{{ useRequestClass }}'],
                ['{{namespace}}', '{{rootNamespace}}', '{{namespacedUserModel}}', '{{requestClass}}', '{{useRequestClass}}'],
            ];
        } else {
            $searches = [
                ['DummyNamespace', 'DummyRootNamespace', 'NamespacedDummyUserModel'],
                ['{{ namespace }}', '{{ rootNamespace }}', '{{ namespacedUserModel }}'],
                ['{{namespace}}', '{{rootNamespace}}', '{{namespacedUserModel}}'],
            ];
        }

        foreach ($searches as $search) {
            $stub = str_replace(
                $search,
                [$this->getNamespace($name), $this->rootNamespace(), $this->userProviderModel(), $this->getRequestClass(), $this->getRequestClass(true)],
                $stub
            );
        }

        return $this;
    }

    protected function getRequestClass($full = false)
    {
        $requestNamespace = 'App\\Websocket\\Requests';
        $class = $this->getNameInput(true);

        if (!$full) {
            $explode = explode('\\', $class);
            return $explode[count($explode)-1] . '::class';
        } else {
            return $requestNamespace . '\\' . $class;
        }
    }
}
