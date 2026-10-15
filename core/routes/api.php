<?php

use App\Http\Controllers\Api\QueryController;
use Illuminate\Support\Facades\Route;

// Axis IQ's only v1 endpoint: a read-only natural-language query.
// `iq.caller` authenticates the CALLER (e.g. a product's backend proxy)
// against IQ_CALLER_TOKEN — required because a shared-token product
// (Field) has Core hold its own downstream secret and attach it
// regardless of who asked, so without this check anyone who finds this
// URL could query it. A forward_user_token product's per-user credential
// is a separate concern, passed in the request body — see QueryController.
Route::post('/query', QueryController::class)->middleware(['throttle:30,1', 'iq.caller']);
