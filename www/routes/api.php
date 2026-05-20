<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VoyageApiController;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('voyages', VoyageApiController::class);
});