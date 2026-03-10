# Toolzz Chat Frontend

Frontend minimo em Next.js para autenticação, usuarios e chat.

## Requisitos

- Node.js 20+
- Backend rodando em `http://localhost:8000`

## Configuracao

Crie um arquivo `.env.local` baseado em `.env.example`:

```bash
cp .env.example .env.local
```

## Rodar o frontend

```bash
npm run dev
```

Abra [http://localhost:3000](http://localhost:3000).

## Fluxo basico

1. Registrar usuario em `/register`
2. Entrar em `/login`
3. Escolher um usuario e enviar mensagens em `/app`

## WebSocket (opcional)

Para mensagens em tempo real, configure as variaveis `NEXT_PUBLIC_REVERB_*`.
