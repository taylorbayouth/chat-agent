<?php

namespace App\Http\Controllers;

use App\Models\AgentSession;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function list()
    {
        $sessions = AgentSession::with('configuration')
            ->orderBy('last_active_at', 'desc')
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'session_id' => $session->session_id,
                    'name' => $session->configuration->name,
                    'status' => $session->status,
                    'last_active_at' => $session->last_active_at,
                ];
            });

        return response()->json($sessions);
    }

    public function details(string $sessionId)
    {
        $session = AgentSession::where('session_id', $sessionId)
            ->with('configuration')
            ->firstOrFail();

        return response()->json([
            'id' => $session->id,
            'session_id' => $session->session_id,
            'status' => $session->status,
            'configuration' => [
                'id' => $session->configuration->id,
                'name' => $session->configuration->name,
                'description' => $session->configuration->description,
                'parameters' => $session->configuration->parameters,
            ],
            'metadata' => $session->metadata,
            'last_active_at' => $session->last_active_at,
            'created_at' => $session->created_at,
        ]);
    }
}
