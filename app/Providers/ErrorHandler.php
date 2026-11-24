<?php

namespace App\Providers;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Output\ConsoleOutput;
use Illuminate\Contracts\Console\Application;

class ErrorHandler extends ExceptionHandler
{

    public function renderForConsole($output, Throwable $e): void
    {

        if (is_array($output)) {
            $output = new ConsoleOutput();
        }

        if ($this->shouldReport($e)) {
            $this->report($e);
        }

        (new ConsoleApplication($this->container))->renderThrowable($e, $output);
    }
}
