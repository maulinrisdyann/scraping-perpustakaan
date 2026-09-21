<?php

namespace App\Console\Commands\Concerns;

use Illuminate\Support\Facades\Process;

trait RunsPythonScript
{
    protected function runPythonScript(string $folder, string $script, int $timeoutSeconds = 600, array $arguments = []): int
    {
        $venvPython = base_path(
            PHP_OS_FAMILY === 'Windows'
                ? "{$folder}/venv/Scripts/python.exe"
                : "{$folder}/venv/bin/python3"
        );
        $workingDir = base_path($folder);

        if (! is_file($venvPython)) {
            $this->error("Python venv tidak ditemukan di {$venvPython}. Jalankan setup dulu di folder {$folder}/.");

            return self::FAILURE;
        }

        $this->info("Menjalankan {$script} di {$folder}/ ...");

        $result = Process::path($workingDir)
            ->timeout($timeoutSeconds)
            ->run([$venvPython, $script, ...$arguments], function (string $type, string $output) {
                $this->output->write($output);
            });

        if ($result->successful()) {
            $this->info("{$script} selesai.");

            return self::SUCCESS;
        }

        $this->error("{$script} gagal (exit code {$result->exitCode()}).");

        return self::FAILURE;
    }
}
