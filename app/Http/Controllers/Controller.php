<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: "Chat API",
    version: "1.0.0",
    description: "API de Chat em Tempo Real - Sistema de mensagens com WebSockets",
    contact: new OA\Contact(email: "contato@exemplo.com", name: "Suporte API")
)]
#[OA\Server(url: "/api", description: "API Server")]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT",
    description: "Token de autenticação Sanctum. Use: Bearer {token}"
)]
#[OA\Tag(name: "Auth", description: "Autenticação de usuários")]
#[OA\Tag(name: "Users", description: "Gerenciamento de usuários")]
#[OA\Tag(name: "Chat", description: "Mensagens e conversas")]
abstract class Controller
{
    //
}
