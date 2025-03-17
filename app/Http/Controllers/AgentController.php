<?php

namespace App\Http\Controllers;

use App\Models\AgentSession;
use App\Models\AgentConfiguration;
use App\Services\OpenAI\AgentBridgeService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AgentController extends Controller
{
    protected AgentBridgeService $agentBridge;

    public function __construct(AgentBridgeService $agentBridge)
    {
        $this->agentBridge = $agentBridge;
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parameters' => 'required|array',
        ]);

        $configuration = AgentConfiguration::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'parameters' => $validated['parameters'],
        ]);

        $session = AgentSession::create([
            'session_id' => Str::uuid()->toString(),
            'configuration_id' => $configuration->id,
            'status' => 'created',
            'metadata' => [],
            'last_active_at' => now(),
        ]);

        return response()->json([
            'session_id' => $session->session_id,
            'status' => $session->status,
        ]);
    }

    public function start(Request $request, string $sessionId)
    {
        $session = AgentSession::where('session_id', $sessionId)->firstOrFail();
        $configuration = $session->configuration;

        $response = $this->agentBridge->createAgent([
            'agent_id' => $session->session_id,
            'instructions' => $configuration->parameters['instructions'] ?? 'You are a helpful assistant.',
            'model' => $configuration->parameters['model'] ?? 'computer-use-preview',
        ]);

        if (!isset($response['error'])) {
            $session->update([
                'status' => 'running',
                'last_active_at' => now(),
            ]);

            return response()->json([
                'session_id' => $session->session_id,
                'status' => $session->status,
            ]);
        }

        return response()->json($response, 500);
    }

    public function interact(Request $request, string $sessionId)
    {
        $session = AgentSession::where('session_id', $sessionId)->firstOrFail();
        
        $validated = $request->validate([
            'input' => 'required|string',
        ]);

        if ($session->status !== 'running') {
            return response()->json([
                'error' => true,
                'message' => 'Session is not running',
            ], 400);
        }

        $response = $this->agentBridge->runAgent($session->session_id, $validated['input']);
        
        // Update session last active time
        $session->update([
            'last_active_at' => now(),
        ]);

        return response()->json($response);
    }

    public function status(string $sessionId)
    {
        $session = AgentSession::where('session_id', $sessionId)->firstOrFail();
        
        return response()->json([
            'session_id' => $session->session_id,
            'status' => $session->status,
            'last_active_at' => $session->last_active_at,
        ]);
    }
}
