<!DOCTYPE html>
<html>
<head>
    <title>WebSocket Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <h1>🧪 WebSocket Connection Test</h1>

    <div id="status">
        <p>Status: <span id="connection-status">⏳ Connecting...</span></p>
        <p>User ID: {{ auth()->check() ? auth()->user()->id : 'Not logged in' }}</p>
        <p>User Role: {{ auth()->check() ? auth()->user()->role : 'N/A' }}</p>
    </div>

    <div id="logs" style="background: #f5f5f5; padding: 10px; margin-top: 20px; font-family: monospace;">
        <h3>Connection Logs:</h3>
        <div id="log-content"></div>
    </div>

    <!-- Pusher and Laravel Echo -->
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.15.3/dist/echo.iife.js"></script>

    <script>
        const logDiv = document.getElementById('log-content');
        const statusSpan = document.getElementById('connection-status');

        function log(message, color = 'black') {
            const timestamp = new Date().toLocaleTimeString();
            const line = document.createElement('div');
            line.style.color = color;
            line.textContent = `[${timestamp}] ${message}`;
            logDiv.appendChild(line);
            console.log(message);
        }

        log('🔧 Initializing Echo...', 'blue');

        // Initialize Echo (use 'pusher' broadcaster, Reverb is Pusher-compatible)
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: '{{ env('REVERB_APP_KEY') }}',
            cluster: 'mt1', // Required by Pusher, ignored by Reverb
            wsHost: '{{ env('REVERB_HOST', '127.0.0.1') }}',
            wsPort: {{ env('REVERB_PORT', 6001) }},
            wssPort: {{ env('REVERB_PORT', 6001) }},
            forceTLS: false,
            disableStats: true,
            encrypted: false,
            enabledTransports: ['ws', 'wss'],
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    @if(auth()->check())
                    'Authorization': 'Bearer {{ session('frontend_token') ?? session('jwt_token') }}'
                    @endif
                }
            }
        });

        log('✅ Echo initialized', 'green');
        log(`Config: key={{ env('REVERB_APP_KEY') }}, host={{ env('REVERB_HOST') }}, port={{ env('REVERB_PORT') }}`, 'gray');

        // Connection events
        if (window.Echo.connector && window.Echo.connector.pusher) {
            const pusher = window.Echo.connector.pusher;

            pusher.connection.bind('connecting', () => {
                log('⏳ Connecting to WebSocket...', 'orange');
                statusSpan.textContent = '⏳ Connecting...';
                statusSpan.style.color = 'orange';
            });

            pusher.connection.bind('connected', () => {
                log('✅ WebSocket connected successfully!', 'green');
                statusSpan.textContent = '✅ Connected';
                statusSpan.style.color = 'green';
            });

            pusher.connection.bind('unavailable', () => {
                log('❌ WebSocket unavailable', 'red');
                statusSpan.textContent = '❌ Unavailable';
                statusSpan.style.color = 'red';
            });

            pusher.connection.bind('failed', () => {
                log('❌ WebSocket connection failed', 'red');
                statusSpan.textContent = '❌ Failed';
                statusSpan.style.color = 'red';
            });

            pusher.connection.bind('disconnected', () => {
                log('⚠️ WebSocket disconnected', 'orange');
                statusSpan.textContent = '⚠️ Disconnected';
                statusSpan.style.color = 'orange';
            });

            pusher.connection.bind('error', (error) => {
                log(`❌ WebSocket error: ${JSON.stringify(error)}`, 'red');
            });
        }

        // Subscribe to channel if logged in
        @if(auth()->check())
        setTimeout(() => {
            log('📡 Subscribing to private channel: user.{{ auth()->user()->id }}.orders', 'blue');

            window.Echo.private('user.{{ auth()->user()->id }}.orders')
                .listen('.order.status.updated', (data) => {
                    log(`📦 Order status updated: ${JSON.stringify(data)}`, 'purple');
                    alert(`Order ${data.order_number} status changed!`);
                })
                .error((error) => {
                    log(`❌ Channel subscription error: ${JSON.stringify(error)}`, 'red');
                });

            log('✅ Subscribed to channel', 'green');
        }, 2000);
        @else
        log('⚠️ User not logged in, skipping channel subscription', 'orange');
        @endif
    </script>
</body>
</html>
