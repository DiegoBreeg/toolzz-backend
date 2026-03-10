<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use OpenApi\Attributes as OA;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    #[OA\Post(
        path: "/register",
        tags: ["Auth"],
        summary: "Registrar novo usuário",
        description: "Cria uma nova conta de usuário e retorna token de autenticação",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "email", "password", "password_confirmation"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "João Silva"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "joao@exemplo.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "senha123"),
                    new OA\Property(property: "password_confirmation", type: "string", format: "password", example: "senha123")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Usuário registrado com sucesso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "User registered successfully."),
                        new OA\Property(property: "user", type: "object"),
                        new OA\Property(property: "token", type: "string", example: "1|abc123...")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Erro de validação")
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->string('password')),
        ]);

        event(new Registered($user));

        Auth::login($user);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully.',
            'user' => $user->only(['id', 'name', 'email', 'created_at']),
            'token' => $token,
        ], 201);
    }
}
