<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VoyageApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Routes API protégées par Sanctum. Le préfixe de nom 'api.' évite
| les conflits avec les routes web qui ont les mêmes noms de ressources
| (par exemple : 'voyages.index' existe en web ET en API).
|
*/

Route::middleware('auth:sanctum')->name('api.')->group(function () {
    Route::apiResource('voyages', VoyageApiController::class);
    Route::get('voyages/{voyage}/participants', [VoyageApiController::class, 'participants'])
        ->name('voyages.participants');
});
