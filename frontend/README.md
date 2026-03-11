# Chat Frontend

Frontend Next.js para o Chat API.

> **Documentação completa:** Veja o [README principal](../README.md) na raiz do projeto.

## Início Rápido

```bash
# Instalar dependências
npm install

# Configurar ambiente
cp .env.example .env.local

# Rodar em desenvolvimento
npm run dev
```

Acesse http://localhost:3000

## Scripts

| Comando | Descrição |
|---------|-----------|
| `npm run dev` | Servidor de desenvolvimento |
| `npm run build` | Build de produção |
| `npm run start` | Servidor de produção |
| `npm run lint` | Verificação de código |

## Estrutura

```
src/
├── app/           # App Router (páginas)
│   ├── (app)/     # Páginas autenticadas
│   └── (auth)/    # Login/Registro
├── components/    # Componentes React
└── lib/           # Utilitários (API, WebSocket)
```
