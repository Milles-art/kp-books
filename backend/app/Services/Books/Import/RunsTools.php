<?php

namespace App\Services\Books\Import;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/** Helpers for formats that need a command line tool on the server. */
trait RunsTools
{
    /** Find a tool by its config key, or explain exactly what to install. */
    protected function tool(string $key, string $package): string
    {
        $name = config("kpbook.tools.{$key}");
        $found = is_file((string) $name) ? $name : (new ExecutableFinder())->find((string) $name);

        if (! $found) {
            throw new ImportException("The server needs {$package} installed to read this kind of file.");
        }

        return $found;
    }

    protected function toolExists(string $key): bool
    {
        $name = config("kpbook.tools.{$key}");

        return is_file((string) $name) || (new ExecutableFinder())->find((string) $name) !== null;
    }

    /** Run a command (arguments as an array, never through a shell). */
    protected function run(array $command, int $timeout): string
    {
        $process = new Process($command);
        $process->setTimeout($timeout);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ImportException('Reading the file failed: ' . mb_substr(trim($process->getErrorOutput()), 0, 200));
        }

        return $process->getOutput();
    }

    protected function tempDir(): string
    {
        $dir = sys_get_temp_dir() . '/kpbook_' . bin2hex(random_bytes(6));
        mkdir($dir, 0700, true);

        return $dir;
    }

    protected function removeDir(string $dir): void
    {
        if (is_dir($dir)) {
            \Illuminate\Support\Facades\File::deleteDirectory($dir);
        }
    }
}
