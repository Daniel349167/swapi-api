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
     * Asigna un rol a un usuario.
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
     * Revoca el rol de un usuario.
     */
    public function revokeRole(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $user->role_id = null;
        $user->save();

        return response()->json(['message' => 'Rol revocado correctamente.']);
    }

    /**
     * Crea un nuevo usuario.
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
     * Actualiza la información de un usuario.
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
     * Elimina un usuario.
     */
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'Usuario eliminado correctamente.']);
    }

    /**
     * Obtiene el historial de consultas de un usuario.
     * Si se pasa un ID de usuario, filtra las consultas de ese usuario;
     * de lo contrario, retorna todos los logs.
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
