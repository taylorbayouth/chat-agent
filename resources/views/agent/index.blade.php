<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Agent Platform</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <div id="app" class="container mx-auto py-8 px-4">
        <h1 class="text-3xl font-bold mb-8">AI Agent Platform</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Agent Control Panel -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Agent Control</h2>
                
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Agent Name</label>
                    <input type="text" id="agentName" class="w-full border rounded px-3 py-2" value="Browser Assistant">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Instructions</label>
                    <textarea id="agentInstructions" class="w-full border rounded px-3 py-2 h-24">You are a helpful assistant that can control the computer browser. Follow user instructions carefully.</textarea>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Model</label>
<select id="agentModel" class="w-full border rounded px-3 py-2">
    <option value="computer-use-preview">computer-use-preview</option>
    <option value="gpt-4o">gpt-4o</option>
    <option value="gpt-4">gpt-4</option>
</select>

                </div>
                
                <div class="flex space-x-2">
                    <button id="createAgentBtn" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Create Agent</button>
                    <button id="startAgentBtn" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600" disabled>Start Agent</button>
                    <button id="stopAgentBtn" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600" disabled>Stop Agent</button>
                </div>
            </div>
            
            <!-- Interaction Panel -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Interaction</h2>
                
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Message</label>
                    <textarea id="userMessage" class="w-full border rounded px-3 py-2 h-24"></textarea>
                </div>
                
                <button id="sendMessageBtn" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600" disabled>Send Message</button>
            </div>
        </div>
        
        <!-- Screen View -->
        <div class="mt-8 bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Screen View</h2>

<div id="screenContainer" class="border rounded p-2 overflow-hidden flex justify-center">
    <img id="screenImage" class="max-w-full object-contain" src="" alt="Agent's view">
    <p id="screenshotStatus" class="text-gray-500 text-center hidden">Waiting for screenshot...</p>
</div>
        </div>
        
        <!-- Conversation -->
        <div class="mt-8 bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Conversation</h2>
            <div id="conversation" class="border rounded p-4 h-96 overflow-y-auto"></div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let sessionId = null;
            let refreshInterval = null;
            
            // Get DOM elements
            const createAgentBtn = document.getElementById('createAgentBtn');
            const startAgentBtn = document.getElementById('startAgentBtn');
            const stopAgentBtn = document.getElementById('stopAgentBtn');
            const sendMessageBtn = document.getElementById('sendMessageBtn');
            const agentName = document.getElementById('agentName');
            const agentInstructions = document.getElementById('agentInstructions');
            const agentModel = document.getElementById('agentModel');
            const userMessage = document.getElementById('userMessage');
            const conversation = document.getElementById('conversation');
            const screenImage = document.getElementById('screenImage');
            
            // Set up CSRF token for AJAX requests
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            // Create agent
            createAgentBtn.addEventListener('click', function() {
                this.disabled = true;
                
                fetch('/api/agents', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        name: agentName.value,
                        description: 'Created via web interface',
                        parameters: {
                            instructions: agentInstructions.value,
                            model: agentModel.value
                        }
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.session_id) {
                        sessionId = data.session_id;
                        appendToConversation('System', `Agent created with session ID: ${sessionId}`);
                        startAgentBtn.disabled = false;
                    } else {
                        appendToConversation('Error', 'Failed to create agent: ' + (data.message || 'Unknown error'));
                        this.disabled = false;
                    }
                })
                .catch(error => {
                    appendToConversation('Error', 'Failed to create agent: ' + error.message);
                    this.disabled = false;
                });
            });
            
            // Start agent
            startAgentBtn.addEventListener('click', function() {
                this.disabled = true;
                
                fetch(`/api/agents/${sessionId}/start`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'running') {
                        appendToConversation('System', 'Agent started successfully.');
                        stopAgentBtn.disabled = false;
                        sendMessageBtn.disabled = false;
                        
                        // Start screen refresh
                        startScreenRefresh();
                    } else {
                        appendToConversation('Error', 'Failed to start agent: ' + (data.message || 'Unknown error'));
                        this.disabled = false;
                    }
                })
                .catch(error => {
                    appendToConversation('Error', 'Failed to start agent: ' + error.message);
                    this.disabled = false;
                });
            });
            
            // Send message
            sendMessageBtn.addEventListener('click', function() {
                const message = userMessage.value.trim();
                
                if (!message) {
                    return;
                }
                
                this.disabled = true;
                appendToConversation('User', message);
                
                fetch(`/api/agents/${sessionId}/interact`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        input: message
                    })
                })
                .then(response => response.json())
                .then(data => {
                    this.disabled = false;
                    
                    if (data.success) {
                        appendToConversation('Agent', data.output || 'Task completed successfully.');
                    } else {
                        appendToConversation('Error', 'Failed to interact with agent: ' + (data.error || 'Unknown error'));
                    }
                    
                    userMessage.value = '';
                })
                .catch(error => {
                    this.disabled = false;
                    appendToConversation('Error', 'Failed to interact with agent: ' + error.message);
                });
            });
            
            // Function to append message to conversation
            function appendToConversation(sender, message) {
                const messageDiv = document.createElement('div');
                messageDiv.className = 'mb-3';
                
                const senderSpan = document.createElement('span');
                senderSpan.className = sender === 'User' ? 'font-bold text-blue-600' : 
                                      sender === 'Agent' ? 'font-bold text-green-600' : 
                                      sender === 'Error' ? 'font-bold text-red-600' : 'font-bold text-gray-600';
                senderSpan.textContent = sender + ': ';
                
                const messageSpan = document.createElement('span');
                messageSpan.textContent = message;
                
                messageDiv.appendChild(senderSpan);
                messageDiv.appendChild(messageSpan);
                
                conversation.appendChild(messageDiv);
                
                // Scroll to bottom
                conversation.scrollTop = conversation.scrollHeight;
            }
            
            // Function to start screen refresh
            function startScreenRefresh() {
                // Stop any existing interval
                if (refreshInterval) {
                    clearInterval(refreshInterval);
                }
                
                // Update screen immediately
                updateScreenshot();
                
                // Then refresh every 2 seconds
                refreshInterval = setInterval(updateScreenshot, 2000);
            }
            
            // Function to update screenshot


function updateScreenshot() {
    if (sessionId) {
        // Add a timestamp to prevent caching
        fetch(`/api/agents/${sessionId}/screenshot?t=${Date.now()}`, {
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.image) {
                screenImage.src = `data:image/webp;base64,${data.image}`;
                screenImage.style.display = 'block';
                document.getElementById('screenshotStatus').classList.add('hidden');
            } else {
                screenImage.style.display = 'none';
                document.getElementById('screenshotStatus').classList.remove('hidden');
            }
        })
        .catch(error => {
            console.error('Error fetching screenshot:', error);
            screenImage.style.display = 'none';
            document.getElementById('screenshotStatus').classList.remove('hidden');
        });
    }
}

        });
    </script>
</body>
</html>
