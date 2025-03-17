<?php

namespace App\Services\System;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Exception;

class ProcessManager
{
    private array $processes = [];
    private string $basePath;

    public function __construct()
    {
        $this->basePath = base_path();
    }

    public function startService(string $serviceName): bool
    {
        try {
            switch ($serviceName) {
                case 'python':
                    $process = new Process(['python', 'run.py'], "{$this->basePath}/python");
                    break;
                case 'node':
                    $process = new Process(['node', 'src/index.js'], "{$this->basePath}/node");
                    break;
                default:
                    throw new Exception("Unknown service: {$serviceName}");
            }

            $process->start();
            $this->processes[$serviceName] = $process;

            Log::info("Started {$serviceName} service", [
                'pid' => $process->getPid(),
            ]);

            return true;
        } catch (Exception $e) {
            Log::error("Failed to start {$serviceName} service", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    public function stopService(string $serviceName): bool
    {
        if (!isset($this->processes[$serviceName])) {
            return false;
        }

        try {
            $process = $this->processes[$serviceName];
            
            if ($process->isRunning()) {
                $process->stop();
                
                Log::info("Stopped {$serviceName} service", [
                    'pid' => $process->getPid(),
                ]);
            }
            
            unset($this->processes[$serviceName]);
            return true;
        } catch (Exception $e) {
            Log::error("Failed to stop {$serviceName} service", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    public function getServiceStatus(string $serviceName): array
    {
        if (!isset($this->processes[$serviceName])) {
            return [
                'running' => false,
                'pid' => null,
            ];
        }

        $process = $this->processes[$serviceName];
        
        return [
            'running' => $process->isRunning(),
            'pid' => $process->isRunning() ? $process->getPid() : null,
        ];
    }
}
