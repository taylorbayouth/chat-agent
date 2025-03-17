<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SystemController;


Route::get('/', function () {
    return view('agent.index');
});

Route::prefix('api')->group(function () {
    Route::prefix('agents')->group(function () {
        Route::post('/', [AgentController::class, 'create']);
        Route::post('/{sessionId}/start', [AgentController::class, 'start']);
        Route::post('/{sessionId}/interact', [AgentController::class, 'interact']);
        Route::get('/{sessionId}/status', [AgentController::class, 'status']);
    });
    
    Route::prefix('system')->group(function () {
        Route::get('/status', [SystemController::class, 'status']);
        Route::post('/start', [SystemController::class, 'start']);
        Route::post('/stop', [SystemController::class, 'stop']);
    });

    Route::prefix('sessions')->group(function () {
        Route::get('/', [SessionController::class, 'list']);
        Route::get('/{sessionId}', [SessionController::class, 'details']);
    });
});


Route::get('/api/agents/{sessionId}/screenshot', function($sessionId) {
    // This fetches the screenshot directly from the Node.js service
    // We do this to avoid Python service timeouts
    $nodeServiceUrl = config('openai.node_service_url', 'http://localhost:3001');
    
    try {
        $response = Http::get("{$nodeServiceUrl}/screenshot");
        
        if ($response->successful()) {
            return $response->json();
        }
        
        return response()->json([
            'success' => false,
            'error' => 'Failed to get screenshot from Node service'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Exception: ' . $e->getMessage()
        ]);
    }
});
