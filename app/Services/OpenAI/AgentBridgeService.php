<?php

namespace App\Services\OpenAI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class AgentBridgeService
{
    private string $pythonServiceUrl;

public function __construct()
{
    $this->pythonServiceUrl = config('openai.python_service_url', 'http://localhost:5001');
}

public function createAgent(array $config)
{
    try {
        Log::info('Creating agent via Python service', [
            'url' => "{$this->pythonServiceUrl}/agents",
            'config' => array_merge($config, ['parameters' => '[REDACTED]'])
        ]);
        
        $response = Http::post("{$this->pythonServiceUrl}/agents", $config);

        if ($response->successful()) {
            Log::info('Agent created successfully', [
                'response' => $response->json()
            ]);
            return $response->json();
        }

        Log::error('Error creating agent via Python service', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return [
            'error' => true,
            'message' => 'Error creating agent: ' . $response->status() . ' - ' . $response->body(),
            'details' => $response->json(),
        ];
    } catch (Exception $e) {
        Log::error('Exception in createAgent', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return [
            'error' => true,
            'message' => 'Exception in createAgent: ' . $e->getMessage(),
        ];
    }
}


public function runAgent(string $agentId, string $input)
{
    try {
        Log::info('Running agent', [
            'agent_id' => $agentId,
            'input' => $input
        ]);

        $response = Http::timeout(30)  // Add a longer timeout
            ->post("{$this->pythonServiceUrl}/agents/{$agentId}/run", [
                'input' => $input
            ]);

        Log::info('Agent run response', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('Error running agent via Python service', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return [
            'error' => true,
            'message' => 'Error running agent: ' . $response->body(),
            'details' => $response->json(),
        ];
    } catch (Exception $e) {
        Log::error('Exception in runAgent', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return [
            'error' => true,
            'message' => 'Exception in runAgent: ' . $e->getMessage(),
        ];
    }
}


}
