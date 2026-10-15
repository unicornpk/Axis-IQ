<?php

use App\Http\Controllers\Api\QueryController;
use Illuminate\Support\Facades\Route;

// Axis IQ's only v1 endpoint: a read-only natural-language query.
// Auth is enforced by the product's own /api/iq/* connector, not here —
// this route just forwards whatever bearer token it was given.
Route::post('/query', QueryController::class)->middleware('throttle:30,1');
