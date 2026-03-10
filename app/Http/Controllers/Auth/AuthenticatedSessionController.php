<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    #[OA\Post(
        path: "/login",
        tags: ["Auth"],
        summary: "Autenticar usuário",
        description: "Realiza login e retorna token de autenticação",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "joao@exemplo.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "senha123")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Login realizado com sucesso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Login successful."),
                        new OA\Property(property: "user", type: "object"),
                        new OA\Property(property: "token", type: "string", example: "1|abc123...")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Credenciais inválidas")
        ]
    )]
    public function store(LoginRequest $request): JsonResponse
    {
        $request->authenticate();

        $user = $request->user();

        // Revoke old tokens and create a new one
        $user->tokens()->delete();
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'user' => $user->only(['id', 'name', 'email', 'created_at']),
            'token' => $token,
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    #[OA\Post(
        path: "/logout",
        tags: ["Auth"],
        summary: "Encerrar sessão",
        description: "Revoga o token de autenticação atual",
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Logout realizado com sucesso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Logout successful.")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Não autenticado")
        ]
    )]
    public function destroy(Request $request): JsonResponse
    {
        // Revoke the current token
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logout successful.',
        ]);
    }
}
