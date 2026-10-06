<?php

use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MeadowController;
use App\Http\Controllers\Api\PrismController;
use App\Http\Controllers\Api\RecordController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return new UserResource($request->user()->loadMissing('profile'));
})->middleware('auth:sanctum');

// PUBLIC ROUTES
Route::post('/login', [AuthController::class, 'login']);

Route::post('/register', [AuthController::class, 'register']);

Route::get('/blooming-meadow', [MeadowController::class, 'index']);


// protected routes can be grouped and potected with suth middleware

// prende come parametri
//
// 1. il middleware che vogliamo frapporre
// 2. una callback function che chiama le rotte da inserire

Route::group(['as' => 'api.', 'middleware' => ['auth:sanctum'] ], function() {

        Route::post('/logout', [AuthController::class, 'logout']);
        Route::apiResource('/records', RecordController::class);
        // store + update of the caller's own bio are the same operation on
        // the hasOne profile, so one endpoint covers both
        Route::put('/user/bio', [UserController::class, 'updateBio']);
        Route::get('/prism', [PrismController::class, 'index']);
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    }
); 