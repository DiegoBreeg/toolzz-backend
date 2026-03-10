<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    /**
     * List/search users excluding the authenticated user.
     */
    #[OA\Get(
        path: "/users",
        tags: ["Users"],
        summary: "Listar usuários",
        description: "Lista todos os usuários ou busca por nome/email. Exclui o usuário autenticado.",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "q", in: "query", description: "Termo de busca", required: false, schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Lista de usuários"),
            new OA\Response(response: 401, description: "Não autenticado")
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['sometimes', 'string', 'max:255'],
        ]);

        $authUserId = $request->user()->id;
        $query = $validated['q'] ?? null;

        if ($query) {
            $users = User::search($query)
                ->query(function ($builder) use ($authUserId) {
                    $builder->where('id', '!=', $authUserId)
                            ->select(['id', 'name', 'email', 'created_at']);
                })
                ->paginate(20);
        } else {
            $users = User::where('id', '!=', $authUserId)
                ->select(['id', 'name', 'email', 'created_at'])
                ->orderBy('name')
                ->paginate(20);
        }

        return response()->json([
            'message' => 'Users retrieved successfully.',
            'data' => $users,
        ]);
    }

    /**
     * Get a specific user's profile.
     */
    #[OA\Get(
        path: "/users/{id}",
        tags: ["Users"],
        summary: "Ver perfil de usuário",
        description: "Retorna os dados de um usuário específico",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", description: "ID do usuário", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Dados do usuário"),
            new OA\Response(response: 404, description: "Usuário não encontrado")
        ]
    )]
    public function show(Request $request, User $user): JsonResponse
    {
        return response()->json([
            'message' => 'User retrieved successfully.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
            ],
        ]);
    }

    /**
     * Update the authenticated user's profile.
     */
    #[OA\Put(
        path: "/user",
        tags: ["Users"],
        summary: "Atualizar perfil",
        description: "Atualiza os dados do usuário autenticado",
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "email", type: "string", format: "email")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Perfil atualizado"),
            new OA\Response(response: 422, description: "Erro de validação")
        ]
    )]
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $request->user()->id],
        ]);

        $request->user()->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'data' => $request->user()->only(['id', 'name', 'email', 'created_at']),
        ]);
    }

    /**
     * Delete the authenticated user's account.
     */
    #[OA\Delete(
        path: "/user",
        tags: ["Users"],
        summary: "Deletar conta",
        description: "Remove permanentemente a conta do usuário autenticado",
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Conta deletada"),
            new OA\Response(response: 401, description: "Não autenticado")
        ]
    )]
    public function destroy(Request $request): JsonResponse
    {
        $request->user()->delete();

        return response()->json([
            'message' => 'Account deleted successfully.',
        ]);
    }
}
