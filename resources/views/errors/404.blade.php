@include('errors.error', [
    'status' => 404,
    'title' => 'Page not found',
    'message' => 'The page you requested may have been moved, deleted, or the address may be incorrect.',
    'image' => asset('image/errors/404-page-not-found.png'),
    'imageAlt' => '404 page not found illustration',
])
