<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SwapiController;
use App\Models\SearchLog;
use App\Http\Controllers\AdminController;
use Illuminate\Http\Request;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/character/{id}', [SwapiController::class, 'getCharacter']);
    Route::get('/planet/{id}', [SwapiController::class, 'getPlanet']);
    Route::get('/film/{id}', [SwapiController::class, 'getFilm']);
    Route::get('/search-logs', function (Request $request) {
        if (in_array($request->user()->role->name, ['Admin', 'Moderator'])) {
            return SearchLog::with('user')->get();
        } else {
            return SearchLog::where('user_id', $request->user()->id)->get();
        }
    });
    
    Route::middleware('role:Admin')->group(function () {
        Route::post('/assign-role', [AdminController::class, 'assignRole']);
        Route::post('/revoke-role/{userId}', [AdminController::class, 'revokeRole']);
        Route::post('/users', [AdminController::class, 'createUser']);
        Route::put('/users/{id}', [AdminController::class, 'updateUser']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);
        Route::get('/logs', [AdminController::class, 'getLogsByUser']);
        Route::get('/users/{userId}/logs', [AdminController::class, 'getLogsByUser']);
    });
});
