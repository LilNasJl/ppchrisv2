@include('errors.error', [
    'status' => 503,
    'title' => 'Service temporarily unavailable',
    'message' => 'The system is temporarily unavailable while maintenance or an update is in progress. Please try again shortly.',
    'image' => asset('image/errors/503-service-unavailable.png'),
    'imageAlt' => '503 service unavailable illustration',
])
