<?php

use Illuminate\Support\Facades\Route;

// Axis IQ Core is a backend API with no UI of its own — nothing for a
// browser to usefully land on here. This replaces Laravel's default
// scaffolded "welcome" marketing page, which otherwise confuses anyone
// who visits the bare URL into thinking a site is half-built.
Route::get('/', function () {
    return response()->json([
        'service' => 'Axis IQ Core',
        'status' => 'ok',
    ]);
});
