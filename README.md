# BeautyCad MVP

Sistema MVP para agilizar cadastro em promocoes de brindes com fluxo de usuarios, dados pessoais, configuracoes e processamento assinado por fila.

## Stack

- Laravel 12 (PHP 8.2+)
- Breeze (Blade + Tailwind)
- SQLite (default local)
- Queue com driver `database`
- Worker Node.js com Playwright para automacao real no navegador

## Funcionalidades implementadas

- Login, cadastro e logout de usuarios.
- Menu com:
  - `Pagina inicial`
  - `Dados pessoais`
  - `Configuracoes`
  - `Sair`
- Validacao obrigatoria de dados pessoais antes de enviar promocao.
- Tela inicial com campo de link + botao `Cadastrar promocao`.
- Registro de submissao de promocao com historico e status.
- Tela de detalhes por promocao com timeline de logs e metadata tecnico.
- Job assinado em fila para processamento.
- Automacao real com Playwright:
  - acessa o link informado e tenta rejeitar cookies
  - mapeia e preenche campos por heuristica (nome, cpf, data nasc., telefone, endereco etc.)
  - identifica captcha e retorna `captcha_required`
  - retorna `needs_info` quando campos obrigatorios ficam pendentes
  - tenta clicar no botao de envio quando encontrado
- Fallback opcional para simulacao via `AUTOMATION_DRIVER=simulated`

## Status de processamento

- `pending`
- `processing`
- `needs_info`
- `captcha_required`
- `completed`
- `failed`

## Setup local

1. Instalar dependencias PHP:

```bash
composer install
```

2. Instalar dependencias front:

```bash
npm install
```

3. Preparar ambiente:

```bash
cp .env.example .env
php artisan key:generate
```

4. Criar banco SQLite:

```bash
touch database/database.sqlite
```

5. Rodar migrations:

```bash
php artisan migrate
```

6. Instalar dependencias do worker Playwright:

```bash
cd automation-worker
npm install
npx playwright install chromium
cd ..
```

7. Subir app e build frontend:

```bash
npm run dev
php artisan serve
```

8. Rodar worker da fila (obrigatorio para processar promocoes):

```bash
php artisan queue:work
```

## Configuracao da automacao

Variaveis em `.env`:

```bash
AUTOMATION_DRIVER=playwright
AUTOMATION_NODE_BINARY=node
AUTOMATION_PLAYWRIGHT_SCRIPT=automation-worker/run-playwright.js
AUTOMATION_HEADLESS=true
AUTOMATION_TIMEOUT_SECONDS=180
AUTOMATION_NAVIGATION_TIMEOUT_MS=45000
AUTOMATION_ACTION_TIMEOUT_MS=6000
```

## Fluxo de uso

1. Criar conta e entrar.
2. Preencher `Dados pessoais`.
3. Em `Configuracoes`, aceitar termos para habilitar automacao.
4. Na `Pagina inicial`, colar o link e enviar.
5. Acompanhar status no historico da mesma tela.

## Observacao importante

O worker nao resolve captcha. Quando detecta captcha, o status volta como `captcha_required` para o usuario concluir manualmente.
