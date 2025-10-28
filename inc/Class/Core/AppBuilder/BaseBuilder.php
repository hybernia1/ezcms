<?php

declare(strict_types=1);

namespace Core\AppBuilder;

use Core\App\Application;
use Core\App\Container;
use Core\Routing\Router;

abstract class BaseBuilder
{
    protected Router $router;

    /**
     * @var array<string, mixed>
     */
    protected array $config = [];

    /**
     * @var array<string, callable(Container):mixed>
     */
    private array $serviceDefinitions = [];

    /**
     * @var array<string, mixed>
     */
    private array $sharedInstances = [];

    /**
     * @var array<string, callable(self):void>
     */
    private array $modules = [];

    /**
     * @var list<callable(Application, Container, self):void>
     */
    private array $afterBuildCallbacks = [];

    private bool $modulesProcessed = false;

    public function __construct(?Router $router = null)
    {
        $this->router = $router ?? new Router();
        $this->withShared('router', $this->router);
    }

    /**
     * @param array<string, mixed> $config
     */
    public function withConfig(array $config): static
    {
        $this->config = array_replace_recursive($this->config, $config);

        return $this;
    }

    /**
     * @param callable(self):void $initializer
     */
    public function withModule(string $name, callable $initializer): static
    {
        $this->modules[$name] = $initializer;

        return $this;
    }

    /**
     * @param callable(Container):mixed $factory
     */
    public function withService(string $id, callable $factory): static
    {
        $this->serviceDefinitions[$id] = $factory;

        return $this;
    }

    public function withShared(string $id, mixed $service): static
    {
        $this->sharedInstances[$id] = $service;

        return $this;
    }

    /**
     * @param callable(Application, Container, self):void $callback
     */
    public function afterBuild(callable $callback): static
    {
        $this->afterBuildCallbacks[] = $callback;

        return $this;
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    public function build(?bool $debug = null): Application
    {
        $this->processModules();

        $container = $this->createContainer();
        $debugFlag = $debug ?? (bool) ($this->config['app']['debug'] ?? false);

        $app = $this->instantiateApplication($container, $debugFlag);

        foreach ($this->afterBuildCallbacks as $callback) {
            $callback($app, $container, $this);
        }

        return $app;
    }

    protected function instantiateApplication(Container $container, bool $debug): Application
    {
        return new Application($this->router, $container, $debug);
    }

    protected function createContainer(): Container
    {
        $container = new Container($this->config);

        foreach ($this->serviceDefinitions as $id => $factory) {
            $container->set($id, $factory);
        }

        foreach ($this->sharedInstances as $id => $service) {
            $container->setInstance($id, $service);
        }

        if (!$container->has('router')) {
            $container->setInstance('router', $this->router);
        }

        return $container;
    }

    private function processModules(): void
    {
        if ($this->modulesProcessed) {
            return;
        }

        foreach ($this->modules as $initializer) {
            $initializer($this);
        }

        $this->modulesProcessed = true;
    }
}
