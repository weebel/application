<?php

namespace Weebel\Application\Tests;

use Exception;
use PHPUnit\Framework\TestCase;
use Throwable;
use Weebel\Application\Application;
use Weebel\Contracts\Bootable;
use Weebel\Contracts\ExceptionHandlerInterface;

class ApplicationTest extends TestCase
{
    public function testApplicationCanRunAnyBootableClass(): void
    {
        $this->expectOutputString("hello from the kernel.");
        $app = new Application(
            kernels: ["mock" => MockBootable::class],
            exceptionHandlers: ["mock" => MockExceptionHandler::class]
        );

        $app->run('mock');
    }

    public function testExternalProvidersCanBeBoundToTheApplication(): void
    {
        $this->expectOutputString("hello from the mock provider. hello from the kernel.");
        $app = new Application(
            kernels: ["mock" => MockBootable::class],
            exceptionHandlers: ["mock" => MockExceptionHandler::class],
            providers: [MockProvider::class]
        );
        $app->run('mock');
    }

    public function testIfTheInjectedProviderThrowsExceptionTheExceptionHandlerWouldCatchIt(): void
    {
        $this->expectOutputString("exception from the faulty provider");
        $app = new Application(
            kernels: ["mock" => MockBootable::class],
            exceptionHandlers: ["mock" => MockExceptionHandler::class],
            providers: [FaultyMockProvider::class]
        );
        $app->run('mock');
    }

    public function testIfInputModeIsNotRegisteredThenModeNotFoundExceptionWouldBeThrown(): void
    {
        $this->expectOutputString("mock mode is not registered in the Kernel manager class");
        $app = new Application(
            exceptionHandlers: ["mock" => MockExceptionNotHandler::class],
        );
        $app->run('mock');
    }

    public function testIfTheEnvironmentIsNotDevThenTheDevProvidersWouldNotBeLoaded(): void
    {
        $this->expectOutputString("hello from the dev mock provider. hello from the kernel.");
        $app = new Application(
            kernels: ["mock" => MockBootable::class],
            exceptionHandlers: ["mock" => MockExceptionHandler::class],
            devProviders: [DevMockProvider::class]
        );
        $app->run('mock');

        ob_clean();

        $this->expectOutputString("hello from the dev mock provider. hello from the kernel.");
        $app = new Application(
            env: 'dev',
            kernels: ["mock" => MockBootable::class],
            exceptionHandlers: ["mock" => MockExceptionHandler::class],
            devProviders: [DevMockProvider::class]
        );
        $app->run('mock');

        ob_clean();

        $this->expectOutputString("hello from the kernel.");
        $app = new Application(
            env: 'local',
            kernels: ["mock" => MockBootable::class],
            exceptionHandlers: ["mock" => MockExceptionHandler::class],
            devProviders: [DevMockProvider::class]
        );
        $app->run('mock');
    }
}

class MockBootable implements Bootable
{
    public function boot(): void
    {
        echo "hello from the kernel.";
    }
}

class MockExceptionHandler implements ExceptionHandlerInterface
{
    public function handle(Throwable $exception): void
    {
        echo $exception->getMessage();
    }
}

class MockExceptionNotHandler implements ExceptionHandlerInterface
{
    public function handle(Throwable $exception): void
    {
        throw $exception;
    }
}

class MockProvider implements Bootable
{
    public function boot(): void
    {
        echo "hello from the mock provider. ";
    }
}

class FaultyMockProvider implements Bootable
{
    /**
     * @throws Exception
     */
    public function boot(): void
    {
        throw new Exception("exception from the faulty provider");
    }
}

class DevMockProvider implements Bootable
{
    /**
     * @throws Exception
     */
    public function boot(): void
    {
        echo "hello from the dev mock provider. ";
    }
}
