<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Config\Repository;
use Symfony\Component\Console\Output\ConsoleOutput;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api();
        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
    })
    ->create();


$configRepository = new Repository();

foreach (glob(__DIR__.'/../config/*.php') as $configFile) {
    $name = basename($configFile, '.php');
    $configRepository->set($name, require $configFile);
}

$app->singleton('config', function () use ($configRepository) {
    return $configRepository;
});

$config = $configRepository->get('app', []);

$app->singleton('Illuminate\Contracts\Debug\ExceptionHandler', function ($app) {
    return new class($app) extends \Illuminate\Foundation\Exceptions\Handler {
        public function renderForConsole($output, Throwable $e): void
        {

            if (is_array($output)) {
                $output = new ConsoleOutput();
            }

            if ($this->shouldReport($e)) {
                $this->report($e);
            }

            (new \Symfony\Component\Console\Application())->renderThrowable($e, $output);
        }
    };
});

$providers = $config['providers'] ?? [];
foreach ($providers as $provider) {
    $app->register($provider);
}

$aliases = $config['aliases'] ?? [];
foreach ($aliases as $alias => $class) {
    $app->alias($alias, $class);
}

return $app;
