<?php

use Illuminate\Support\Facades\Route;

// Named 'login' route required by Laravel's auth redirect for unauthenticated
// requests. This is an API/SPA app (the real login UI is the SPA at
// /auth/login), so a server hit here just returns a clean 401 JSON instead of
// throwing "Route [login] not defined" → HTTP 500.
Route::get('login', fn () => response()->json(['message' => 'Unauthenticated.'], 401))->name('login');

Route::view('/{any?}', 'app')->where('any', '^(?!api).*$');
