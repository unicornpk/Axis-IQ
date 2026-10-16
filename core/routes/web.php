<?php

use Illuminate\Support\Facades\Route;

// Marketing/landing page. Machine health checks should use GET /up
// (Laravel's built-in health route) rather than scraping this.
Route::get('/', function () {
    return view('landing');
});

// Functional demo — sample data only, no API calls, no secrets. Safe to
// be fully public; see resources/views/demo.blade.php.
Route::get('/demo', function () {
    return view('demo');
});
