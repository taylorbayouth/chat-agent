<?php

namespace App\Services\OpenAI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class ResponsesService
{
    private string $baseUrl;
    private string $apiKey;
    private array $defaultHeaders;

    public function __construct()
    {
        $this->baseUrl = 'https://api.openai.com/v1/responses';
        $this->apiKey = config('openai.api_key');
        $this->defaultHeaders = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->apiKey,
        ];

        if (config('openai.organization')) {
            $this->defaultHeaders['OpenAI-Organization'] = config('openai.organization');
        }
    }

    public function sendMessage(string $message, array $options = [])
    {
        try {
            $payload = array_merge([
                'model' => config('openai.model', 'gpt-4o'),
                'input' => $message,
                'text' => [
                    'format' => [
                        'type' => 'text'
                    ]
                ],
            ], $options);

            $response = Http::withHeaders($this->defaultHeaders)
                ->post($this->baseUrl, $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('OpenAI API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'error' => true,
                'message' => 'Error from OpenAI API: ' . $response->status(),
                'details' => $response->json(),
            ];
        } catch (Exception $e) {
            Log::error('Exception in ResponsesService', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'error' => true,
                'message' => 'Exception in ResponsesService: ' . $e->getMessage(),
            ];
        }
    }

    public function streamResponse(string $responseId, callable $callback)
    {
        // This method will be implemented to handle streaming responses
        // For now, we're adding a placeholder
    }

    public function getResponse(string $responseId)
    {
        try {
            $response = Http::withHeaders($this->defaultHeaders)
                ->get("{$this->baseUrl}/{$responseId}");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('OpenAI API error when retrieving response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'error' => true,
                'message' => 'Error retrieving response from OpenAI API: ' . $response->status(),
                'details' => $response->json(),
            ];
        } catch (Exception $e) {
            Log::error('Exception in getResponse', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'error' => true,
                'message' => 'Exception in getResponse: ' . $e->getMessage(),
            ];
        }
    }

    public function deleteResponse(string $responseId)
    {
        try {
            $response = Http::withHeaders($this->defaultHeaders)
                ->delete("{$this->baseUrl}/{$responseId}");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('OpenAI API error when deleting response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'error' => true,
                'message' => 'Error deleting response from OpenAI API: ' . $response->status(),
                'details' => $response->json(),
            ];
        } catch (Exception $e) {
            Log::error('Exception in deleteResponse', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'error' => true,
                'message' => 'Exception in deleteResponse: ' . $e->getMessage(),
            ];
        }
    }
}
