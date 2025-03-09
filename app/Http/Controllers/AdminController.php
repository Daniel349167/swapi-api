<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\SearchLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/assign-role",
     *     summary="Asignar un rol a un usuario",
     *     tags={"Admin"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id", "role_id"},
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="role_id", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rol asignado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Rol asignado correctamente.")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function assignRole(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id',
        ]);

        $user = User::findOrFail($request->user_id);
        $user->role_id = $request->role_id;
        $user->save();

        return response()->json(['message' => 'Rol asignado correctamente.']);
    }

    /**
     * @OA\Post(
     *     path="/api/revoke-role/{userId}",
     *     summary="Revocar el rol de un usuario",
     *     tags={"Admin"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="ID del usuario",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rol revocado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Rol revocado correctamente.")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Usuario no encontrado")
     * )
     */
    public function revokeRole(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $user->role_id = null;
        $user->save();

        return response()->json(['message' => 'Rol revocado correctamente.']);
    }

    /**
     * @OA\Post(
     *     path="/api/users",
     *     summary="Crear un nuevo usuario",
     *     tags={"Admin"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password"},
     *             @OA\Property(property="name", type="string", example="Nuevo Usuario"),
     *             @OA\Property(property="email", type="string", format="email", example="nuevo@example.com"),
     *             @OA\Property(property="password", type="string", example="secret123"),
     *             @OA\Property(property="role_id", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Usuario creado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Usuario creado correctamente."),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function createUser(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role_id'  => 'nullable|exists:roles,id',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role_id'  => $request->role_id,
        ]);

        return response()->json(['message' => 'Usuario creado correctamente.', 'user' => $user], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/users/{id}",
     *     summary="Actualizar un usuario",
     *     tags={"Admin"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del usuario a actualizar",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Usuario Actualizado"),
     *             @OA\Property(property="email", type="string", format="email", example="actualizado@example.com"),
     *             @OA\Property(property="password", type="string", example="nuevaContraseña123"),
     *             @OA\Property(property="role_id", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuario actualizado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Usuario actualizado correctamente."),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Usuario no encontrado")
     * )
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name'     => 'sometimes|required|string|max:255',
            'email'    => 'sometimes|required|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|required|string|min:8',
            'role_id'  => 'sometimes|nullable|exists:roles,id',
        ]);

        if ($request->has('name')) {
            $user->name = $request->name;
        }
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }
        if ($request->has('role_id')) {
            $user->role_id = $request->role_id;
        }

        $user->save();

        return response()->json(['message' => 'Usuario actualizado correctamente.', 'user' => $user]);
    }

    /**
     * @OA\Delete(
     *     path="/api/users/{id}",
     *     summary="Eliminar un usuario",
     *     tags={"Admin"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del usuario a eliminar",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuario eliminado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Usuario eliminado correctamente.")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Usuario no encontrado")
     * )
     */
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'Usuario eliminado correctamente.']);
    }

    /**
     * @OA\Get(
     *     path="/api/users/{userId}/logs",
     *     summary="Obtener el historial de búsquedas de un usuario",
     *     tags={"Admin"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="ID del usuario cuyos logs se consultarán",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Historial de búsquedas obtenido",
     *         @OA\JsonContent(type="array", @OA\Items())
     *     )
     * )
     */
    public function getLogsByUser(Request $request, $userId = null)
    {
        if ($userId) {
            $logs = SearchLog::with('user')->where('user_id', $userId)->get();
        } else {
            $logs = SearchLog::with('user')->get();
        }

        return response()->json($logs);
    }
}
