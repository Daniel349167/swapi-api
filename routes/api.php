<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SwapiController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/character/{id}', [SwapiController::class, 'getCharacter']);
    Route::get('/planet/{id}', [SwapiController::class, 'getPlanet']);
    Route::get('/film/{id}', [SwapiController::class, 'getFilm']);
    Route::get('/vehicle/{id}', [SwapiController::class, 'getVehicle']);
    Route::get('/species/{id}', [SwapiController::class, 'getSpecies']);
});
