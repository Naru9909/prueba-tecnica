<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * PREGUNTA: ¿Qué es Laravel Sanctum y cómo funciona para APIs?
 * ¿Cuál es la diferencia entre tokens de API y tokens de sesión (SPA) en Sanctum?
 * ¿Qué alternativas existen: Passport, JWT (tymon/jwt-auth), Breeze, Fortify?
 *
 * PREGUNTA: ¿Por qué NO se usa 'auth:sanctum' middleware en las rutas de este
 * controller (login/register)? ¿Qué pasaría si se pusiera?
 */
class AuthController extends Controller
{
    /**
     * POST /api/v1/auth/register
     *
     * PREGUNTA: ¿Qué pasa con la contraseña antes de guardarse en la base de datos?
     * ¿Dónde ocurre el hash — aquí, en el modelo, o en ambos?
     * (Pista: revisa el cast 'hashed' en User::casts())
     *
     * PREGUNTA: ¿Por qué retornamos 201 en lugar de 200?
     * ¿Cuál es la semántica correcta de cada código HTTP?
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username', 'regex:/^[a-z0-9_]+$/'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        // PREGUNTA: El cast 'hashed' en el modelo User se encarga del bcrypt.
        // ¿Es esto suficiente o deberías llamar Hash::make() explícitamente aquí?
        // ¿Qué problema podría surgir si eliminas el cast pero mantienes este código?
        $user = User::create([
            'name'     => $validated['name'],
            'username' => $validated['username'],
            'email'    => $validated['email'],
            'password' => $validated['password'],
            'role'     => 'author',
            'is_active'=> true,
        ]);

        // PREGUNTA: ¿Qué hace createToken()? ¿Dónde se almacena el token?
        // ¿Qué tabla de base de datos se usa? ¿Qué es el 'plainTextToken'?
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully.',
            'token'   => $token,
            'user'    => new UserResource($user),
        ], 201);
    }

    /**
     * POST /api/v1/auth/login
     *
     * PREGUNTA: ¿Por qué usamos Hash::check() en lugar de comparar
     * directamente las contraseñas? ¿Qué pasa si usas == en vez de Hash::check()?
     *
     * PREGUNTA: ¿Qué es un "timing attack" y cómo Hash::check()
     * ayuda a prevenirlo?
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

        // PREGUNTA: ¿Por qué se verifica la contraseña aunque el usuario no exista?
        // ¿Qué ataque previene esta técnica de "constant-time comparison"?
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Tu cuenta está desactivada. Contacta al administrador.',
            ], 403);
        }

        // PREGUNTA: ¿Es buena práctica revocar todos los tokens anteriores al hacer login?
        // ¿Qué implicaciones tiene para un usuario con múltiples dispositivos?
        // $user->tokens()->delete(); // Descomentar para forzar logout en otros dispositivos

        // PREGUNTA: El segundo argumento 'abilities' de createToken() sirve para...
        // ¿Cómo restringirías un token para que solo pueda leer pero no escribir?
        $token = $user->createToken('auth_token', ['*'])->plainTextToken;

        return response()->json([
            'message' => 'Login exitoso.',
            'token'   => $token,
            'user'    => new UserResource($user),
        ]);
    }

    /**
     * POST /api/v1/auth/logout
     *
     * PREGUNTA: ¿Qué hace $request->user()->currentAccessToken()->delete()?
     * ¿Qué diferencia hay entre eliminar solo el token actual
     * vs todos los tokens del usuario?
     *
     * PREGUNTA: Este endpoint requiere 'auth:sanctum'. Si el token ya expiró
     * o fue eliminado, ¿qué código HTTP retorna Laravel antes de entrar al método?
     */
    public function logout(Request $request): JsonResponse
    {
        // Elimina solo el token actual (no afecta otros dispositivos)
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }

    /**
     * POST /api/v1/auth/logout-all
     *
     * PREGUNTA: ¿Cuándo es apropiado ofrecer un "logout from all devices"?
     * ¿Qué tabla se trunca con este comando?
     */
    public function logoutAll(Request $request): JsonResponse
    {
        // Elimina TODOS los tokens del usuario (logout en todos los dispositivos)
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Sesión cerrada en todos los dispositivos.',
        ]);
    }

    /**
     * GET /api/v1/auth/me
     *
     * PREGUNTA: ¿Por qué este endpoint es útil si ya existe GET /api/v1/me?
     * ¿Podría ser redundante? ¿Cómo lo organizarías mejor?
     */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
