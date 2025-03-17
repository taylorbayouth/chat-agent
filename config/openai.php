<?php

return [
    'api_key' => env('OPENAI_API_KEY', ''),
    'organization' => env('OPENAI_ORG_ID', ''),
    'model' => env('OPENAI_MODEL', 'gpt-4o'),
    'responses_url' => 'https://api.openai.com/v1/responses',
    'python_service_url' => env('PYTHON_SERVICE_URL', 'http://localhost:5000'),
    'node_service_url' => env('NODE_SERVICE_URL', 'http://localhost:3001'),
    'websocket_server' => env('WEBSOCKET_SERVER', 'ws://localhost:3001'),
];
