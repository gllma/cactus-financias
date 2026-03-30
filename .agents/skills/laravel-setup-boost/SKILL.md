---
name: laravel-setup-boost
description: "Instala e configura o Laravel Boost (MCP server) no projeto Laravel atual. O Boost dá ao Claude Code acesso direto ao schema do banco, rotas, config, logs, Artisan e 17k+ docs do ecossistema Laravel"
user-invocable: true
allowed-tools: Read, Write, Edit, Grep, Glob, Bash
argument-hint: "[--ide=claude-code|cursor|copilot]"
---

# Laravel Boost Setup

Instala e configura o [Laravel Boost](https://laravel.com/docs/12.x/ai) no projeto Laravel atual. O Boost é um MCP server que expõe 15+ tools para agentes de IA, dando acesso profundo ao projeto.

## O que o Boost fornece ao Claude Code

| Tool | O que faz |
|---|---|
| Database schema | Inspeciona tabelas, colunas, índices e relacionamentos |
| Routes | Lista todas as rotas registradas com middleware e controllers |
| Config | Acessa configurações do projeto |
| Artisan | Descobre e executa comandos Artisan |
| Logs | Lê e analisa logs da aplicação |
| Tinker | Executa PHP no contexto da aplicação |
| Docs search | Busca em 17k+ docs do ecossistema Laravel |
| Package info | Lista pacotes instalados e versões |

## Passo 1: Verificar pré-requisitos

1. Confirme que é um projeto Laravel:

```bash
# Verificar versão do Laravel
php artisan --version
```

2. Confirme a versão do PHP:

```bash
php -v
# Requer PHP 8.1+
```

3. Se o projeto usa Docker, execute os comandos dentro do container PHP:

```bash
# Detectar se tem docker-compose
ls docker-compose*.yml
# Se sim, usar: docker compose exec php-fpm <comando>
# Ou se tem Makefile: make shell-php
```

## Passo 2: Instalar o pacote

```bash
composer require laravel/boost --dev
```

Se estiver em Docker:
```bash
# Adaptar ao comando do projeto (make composer-require, docker compose exec, etc.)
docker compose exec php-fpm composer require laravel/boost --dev
```

## Passo 3: Executar o instalador

```bash
php artisan boost:install
```

O instalador vai:
- Detectar o IDE/agente em uso
- Gerar `.mcp.json` (configuração MCP)
- Gerar/atualizar `CLAUDE.md` (guidelines do projeto)
- Gerar `boost.json` (configuração do Boost)

## Passo 4: Verificar arquivos gerados

Após a instalação, verifique que foram criados:

1. **`.mcp.json`** — Configuração do MCP server. Deve apontar para o artisan do projeto:

```json
{
  "mcpServers": {
    "boost": {
      "command": "php",
      "args": ["artisan", "boost:mcp"],
      "cwd": "/path/to/project"
    }
  }
}
```

2. **`boost.json`** — Configuração do Boost com tools habilitadas

3. **`CLAUDE.md`** — Se já existir um CLAUDE.md customizado da ae3, **PRESERVAR o conteúdo existente**. O Boost pode tentar sobrescrever. Nesse caso:
   - Faça backup do CLAUDE.md existente antes de instalar
   - Após a instalação, faça merge manual: mantenha as convenções da ae3 e adicione as guidelines geradas pelo Boost

## Passo 5: Ajustar para Docker (se aplicável)

Se o projeto roda em Docker, o `.mcp.json` precisa apontar para o container:

```json
{
  "mcpServers": {
    "boost": {
      "command": "docker",
      "args": ["compose", "exec", "-T", "php-fpm", "php", "artisan", "boost:mcp"],
      "cwd": "/path/to/project"
    }
  }
}
```

Adapte `php-fpm` ao nome do serviço PHP do `docker-compose.yml` do projeto.

## Passo 6: Adicionar ao .gitignore (se necessário)

Verifique se `.mcp.json` deve ser commitado ou ignorado:

- **Commitar** se a equipe toda usa Claude Code/Cursor → todos se beneficiam
- **Ignorar** se só alguns devs usam → adicionar ao `.gitignore`

Recomendação ae3: **commitar** para que toda a equipe tenha acesso.

## Passo 7: Verificar que funciona

Reinicie o Claude Code (ou IDE) e teste:

```
# O Claude Code agora deve ter acesso aos tools do Boost
# Teste pedindo algo que depende de contexto do projeto:
"Liste todas as rotas da API"
"Qual o schema da tabela users?"
"Quais packages estão instalados?"
```

## Passo 8: Integrar com CLAUDE.md existente

Se o projeto já tem um `CLAUDE.md` da ae3, faça merge:

1. Leia o CLAUDE.md gerado pelo Boost
2. Leia o CLAUDE.md da ae3 (se existir)
3. Combine:
   - **Seções do Boost** (guidelines de versão, packages): manter
   - **Seções da ae3** (arquitetura, naming, regras): manter
   - **Conflitos**: priorizar as convenções da ae3

## Regras importantes

- SEMPRE instale como `--dev` (o Boost é ferramenta de desenvolvimento, não de produção)
- SEMPRE preserve o CLAUDE.md existente da ae3 ao instalar (faça backup antes)
- Se o projeto usa Docker, SEMPRE ajuste o `.mcp.json` para apontar ao container
- NUNCA commite API keys ou tokens no `.mcp.json`
- O Boost NÃO substitui as skills da ae3 — ele complementa com contexto em tempo real
