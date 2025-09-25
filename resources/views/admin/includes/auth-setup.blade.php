{{-- Admin Authentication Setup for JavaScript --}}
<script>
// Setup JWT token for axios from PHP session
@if(session('jwt_token'))
    // Set axios default authorization header
    if (typeof axios !== 'undefined') {
        axios.defaults.headers.common['Authorization'] = 'Bearer {{ session('jwt_token') }}';
    }

    // Store in localStorage for consistency with main app
    localStorage.setItem('access_token', '{{ session('jwt_token') }}');

    // Store user data if available
    @if(session('user_data'))
        localStorage.setItem('user_data', JSON.stringify(@json(session('user_data'))));
    @endif
@elseif(Auth::check())
    // Fallback: if session data not available but Laravel auth is working
    console.warn('JWT token not found in session, but Laravel auth is active');
@else
    // No authentication found
    console.error('No authentication found, redirecting to login...');
    setTimeout(() => {
        window.location.href = '/login';
    }, 1000);
@endif

// Setup axios interceptors for admin panel
if (typeof axios !== 'undefined') {
    axios.interceptors.response.use(
        response => response,
        error => {
            if (error.response?.status === 401) {
                console.error('Authentication failed, redirecting to login...');
                localStorage.removeItem('access_token');
                localStorage.removeItem('user_data');
                window.location.href = '/login';
            }
            return Promise.reject(error);
        }
    );
}
</script>