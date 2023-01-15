<?php

namespace Weebel\Application;

use Weebel\Contracts\Bootable;
use Weebel\Contracts\Container;

class Application
{
    protected Container $container;

    protected KernelManager $kernelManager;

    protected string $env = 'dev';

    public function __construct(...$arguments)
    {
        if (array_key_exists('env', $arguments)) {
            $this->env = (string)$arguments['env'];
        }

        $this->container = array_key_exists('container', $arguments) ?
            $arguments['container'] : $this->makeContainer();

        $this->kernelManager = array_key_exists('kernelManager', $arguments) ?
            $arguments['kernelManager']($arguments) : $this->makeKernelManager($arguments);

        $this->registerApp($arguments);
        $this->registerContainer();
        $this->registerKernelManager();
    }

    public function run(string $mode): void
    {
        try {
            $this->loadProviders($this->kernelManager->resolveProviders($mode, $this->env));
            $this->bootBootable($this->container->get(
                $this->kernelManager->getKernel($mode)
            ));
        } catch (\Throwable $throwable) {
            try {
                $this->container->get(
                    $this->kernelManager->resolveExceptionHandler($mode)
                )->handle($throwable);
            } catch (\Throwable $e) {
                echo $throwable->getMessage();
            }
        }
    }

    protected function makeContainer(): Container
    {
        // here it should return a default container instance.
        // It can be overridden by other implementations

        if (class_exists(\Weebel\Container\Container::class)) {
            return \Weebel\Container\Container::getInstance();
        }
    }

    protected function makeKernelManager($arguments): KernelManager
    {
        return new KernelManager($arguments);
    }

    protected function bootBootable(Bootable $bootable): void
    {
        $bootable->boot();
    }

    protected function registerApp($arguments): void
    {
        $this->container->set("app", $this);
        $this->container->set('config', $arguments);
        $this->container->set(__CLASS__, $this);
        $this->container->set(get_class($this), $this);
    }

    private function registerContainer(): void
    {
        $this->container->set(get_class($this->container), $this->container);
        $this->container->set(\Psr\Container\ContainerInterface::class, $this->container);
        $this->container->set(Container::class, $this->container);
    }

    protected function registerKernelManager(): void
    {
        $this->container->set("kernel.manager", $this->kernelManager);
        $this->container->set(KernelManager::class, $this->kernelManager);
        $this->container->set(get_class($this->kernelManager), $this->kernelManager);
    }

    protected function loadProviders(array $providers): void
    {
        foreach ($providers as $provider) {
            $this->bootBootable($this->container->get($provider));
        }
    }
}
