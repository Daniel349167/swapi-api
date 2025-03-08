<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SwapiController;
use App\Models\SearchLog;
use App\Http\Controllers\AdminController;

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
Route::middleware(['auth:sanctum', 'role:Admin'])->group(function () {
    // Asignar y revocar roles
    Route::post('/assign-role', [AdminController::class, 'assignRole']);
    Route::post('/revoke-role/{userId}', [AdminController::class, 'revokeRole']);

    // CRUD de usuarios
    Route::post('/users', [AdminController::class, 'createUser']);       // Crear usuario
    Route::put('/users/{id}', [AdminController::class, 'updateUser']);   // Actualizar usuario
    Route::delete('/users/{id}', [AdminController::class, 'deleteUser']); // Eliminar usuario

    // Historial de consultas
    // - Ruta para obtener los logs de todos los usuarios
    Route::get('/logs', [AdminController::class, 'getLogsByUser']);
    // - Ruta para obtener los logs de un usuario específico
    Route::get('/users/{userId}/logs', [AdminController::class, 'getLogsByUser']);
});
});
