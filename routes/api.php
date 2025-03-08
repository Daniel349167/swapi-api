<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SwapiController;
use App\Models\SearchLog;

use Illuminate\Http\Request;

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
     // Endpoint para ver el historial de búsquedas
     Route::get('/search-logs', function (Request $request) {
        // Si el usuario es Admin o Moderator, puede ver todos los logs
        if (in_array($request->user()->role->name, ['Admin', 'Moderator'])) {
            return SearchLog::with('user')->get();
        } else {
            // Si es Fan, solo se muestran sus propias búsquedas
            return SearchLog::where('user_id', $request->user()->id)->get();
        }
    });
    
});
