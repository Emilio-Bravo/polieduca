<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    // Obtener todos los usuarios (solo admin)
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => User::all()
        ]);
    }

    // Obtener un usuario específico
    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json([
            'status' => 'success',
            'data' => $user
        ]);
    }

    // Crear usuario (registro)
    public function create(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'email' => 'required|email|unique:users',
                'password' => 'required|string|min:8|confirmed'
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => 'user', // Rol por defecto
                'password' => Hash::make($validated['password']) // ¡Usa Hash en lugar de Crypt!
            ]);

            return response()->json([
                'status' => 'created',
                'data' => $user
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'errors' => $e->errors()
            ], 422);
        }
    }

    // Actualizar usuario
    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|string|max:100',
                'email' => [
                    'sometimes',
                    'email',
                    Rule::unique('users')->ignore($user->id)
                ],
                'password' => 'sometimes|string|min:8|confirmed',
                'role' => 'sometimes|in:user,teacher,admin' // Solo roles permitidos
            ]);

            // Actualizar contraseña solo si se proporciona
            if ($request->has('password')) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $user->update($validated);

            return response()->json([
                'status' => 'updated',
                'data' => $user
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['status' => 'User not found'], 404);
        }
    }

    // Eliminar usuario
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);

            // Opcional: Validar que solo el admin o el propio usuario pueda eliminarse
            if (auth()->user()->role !== 'admin' && auth()->user()->id != $id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No tienes permiso para eliminar este usuario'
                ], 403);
            }

            $user->delete();

            return response()->json([
                'status' => 'deleted'
            ], 204);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['status' => 'User not found'], 404);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Credenciales incorrectas'
            ], 401);
        }

        $user = User::where('email', $request->email)->first();

        return response()->json([
            'token' => $user->createToken('API_TOKEN')->plainTextToken,
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Sesión cerrada'
        ]);
    }
}
