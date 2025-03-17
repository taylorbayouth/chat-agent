<?php

namespace App\Http\Controllers;

use App\Services\System\ProcessManager;
use Illuminate\Http\Request;

class SystemController extends Controller
{
    protected ProcessManager $processManager;

    public function __construct(ProcessManager $processManager)
    {
        $this->processManager = $processManager;
    }

    public function status()
    {
        $pythonStatus = $this->processManager->getServiceStatus('python');
        $nodeStatus = $this->processManager->getServiceStatus('node');
        
        return response()->json([
            'python_service' => $pythonStatus,
            'node_service' => $nodeStatus
        ]);
    }

    public function start(Request $request)
    {
        $service = $request->input('service');
        
        if (!in_array($service, ['python', 'node'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid service specified'
            ], 400);
        }
        
        $result = $this->processManager->startService($service);
        
        return response()->json([
            'success' => $result,
            'message' => $result ? 'Service started successfully' : 'Failed to start service'
        ]);
    }

    public function stop(Request $request)
    {
        $service = $request->input('service');
        
        if (!in_array($service, ['python', 'node'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid service specified'
            ], 400);
        }
        
        $result = $this->processManager->stopService($service);
        
        return response()->json([
            'success' => $result,
            'message' => $result ? 'Service stopped successfully' : 'Failed to stop service'
        ]);
    }
}
