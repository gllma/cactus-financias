---
name: laravel-crud
description: "Scaffold completo de CRUD Laravel: Controller, Service, Repository, DTO, FormRequest, Resource, Migration, Factory e Test seguindo os padrões do projeto atual"
user-invocable: true
allowed-tools: Read, Write, Edit, Grep, Glob, Bash, Agent
argument-hint: "[ModelName] [--fields=name:string,status:enum] [--module=ModuleName] [--admin]"
---

# Laravel CRUD Generator

Gera um CRUD completo seguindo os padrões arquiteturais do projeto atual.

## Passo 1: Detectar o padrão arquitetural

Antes de gerar qualquer arquivo, analise o projeto:

1. Leia o `CLAUDE.md` do projeto (se existir) para entender convenções específicas
2. Identifique o padrão arquitetural verificando a estrutura de diretórios:

### Padrão A — Modular (Packages)
Se existir `app/Packages/`:
- Cada módulo é autocontido em `app/Packages/{ModuleName}/`
- Estrutura interna: Controllers/, Services/, Repositories/, DTOs/, Requests/, Resources/, Models/, Enums/, Helpers/
- O `--module` define em qual package gerar. Se não informado, use o nome do Model como módulo
- Verifique se o package já existe. Se sim, gere dentro dele. Se não, crie a estrutura do package
- Verifique como os packages registram rotas (RouteServiceProvider ou routes/ dentro do package)
- Verifique como DTOs são implementados (spatie/laravel-data vs readonly class)
- Referência: vivamobil-api

### Padrão B — DDD com Repository Interface
Se existir `app/Repositories/Contracts/`:
- Models em `app/Models/`
- Controllers em `app/Http/Controllers/Api/V1/`
- Services em `app/Services/{Entity}/`
- Repositories: Interface em `app/Repositories/Contracts/`, Implementação em `app/Repositories/Eloquent/`
- FormRequests em `app/Http/Requests/{Entity}/`
- Resources em `app/Http/Resources/`
- DTOs dentro de `app/Services/{Entity}/Dto/` ou `app/DTOs/`
- Binding no `RepositoryServiceProvider`
- Referência: sigo-api

### Padrão C — Laravel Padrão
Se nenhum dos acima:
- Estrutura padrão do Laravel
- Models em `app/Models/`, Controllers em `app/Http/Controllers/`

3. Analise 1 CRUD existente completo como referência de estilo (escolha o mais recente ou completo)
4. Se `--admin` foi passado, analise os controllers em `Admin/` ou `admin/` para seguir o padrão de endpoints administrativos (permissões, filtros avançados, ações em lote)
5. Identifique o sistema de autenticação:
   - Se `config/sanctum.php` existe e rotas usam `auth:sanctum` → **Sanctum**
   - Se existem stored procedures de login (`admin.process_login`) ou `personal_access_tokens` + `refresh_tokens` customizados → **Auth customizada** (vivamobil usa esse padrão)
   - Isso afeta como os testes fazem autenticação e como os middlewares são aplicados
6. Verifique se o projeto usa PostGIS (procure por `postgis`, `geometry`, `point` nas migrations):
   - Se sim, campos geoespaciais devem usar tipos PostGIS (`$table->point()`, `$table->geometry()`, etc.)
7. Consulte `docs/` (se existir) para regras de negócio que afetem a entidade — campos obrigatórios, validações de domínio, relações com outras entidades

## Passo 2: Interpretar os argumentos

O usuário fornece:
- `$ARGUMENTS` — Nome do Model (ex: `Contract`, `TripBooking`)
- `--fields=` (opcional) — Campos no formato `name:type` separados por vírgula
- `--module=` (opcional) — Nome do módulo/package (Padrão A)

Se não fornecer `--fields`, pergunte quais campos o model terá.
Se o padrão for A e não fornecer `--module`, use o nome do Model como módulo.

## Passo 3: Gerar os arquivos

Gere TODOS os arquivos abaixo, na ordem. Use como base o CRUD existente que você analisou no Passo 1.

### 3.1 Migration
- Nome: `{timestamp}_create_{table_name}_table.php`
- Inclua: campos fornecidos, `timestamps()`, `softDeletes()` se o projeto usar
- Siga o padrão de schema do projeto (ex: `admin.*`, `social.*`, `public.*`)
- Se o projeto usar PostGIS e o Model tiver dados geoespaciais, use tipos adequados: `$table->point('location')`, `$table->geometry('area')`, `$table->geography('route_path')`
- Inclua índices espaciais se aplicável: `$table->spatialIndex('location')`

### 3.2 Model
- Inclua: `$fillable`, `$casts`, relationships (se inferíveis dos campos)
- Adicione traits que o projeto usar (SoftDeletes, HasFactory, etc.)
- Siga o padrão de casts do projeto (ex: enums, dates, json)

### 3.3 Factory
- Em `database/factories/`
- Gere dados fake realistas usando Faker
- Cubra todos os campos do model

### 3.4 Repository
- **Padrão A:** `app/Packages/{Module}/Repositories/{Entity}Repository.php` extendendo BaseRepository
- **Padrão B:** Interface em `app/Repositories/Contracts/{Entity}RepositoryInterface.php` + Implementação em `app/Repositories/Eloquent/{Entity}EloquentRepository.php` extendendo BaseEloquentRepository. Registre o binding no `RepositoryServiceProvider`
- **Padrão C:** Pule (use Eloquent direto no service)

### 3.5 DTO
- **Padrão A:** `app/Packages/{Module}/DTOs/Create{Entity}DTO.php` e `Update{Entity}DTO.php` usando spatie/laravel-data
- **Padrão B:** Readonly class em `app/Services/{Entity}/Dto/` ou `app/DTOs/`
- Inclua todos os campos necessários para create e update

### 3.6 Service
- Crie services separados por ação seguindo o naming do projeto:
  - `Create{Entity}Service`
  - `Update{Entity}Service`
  - `Delete{Entity}Service`
  - `List{Entity}Service` ou `Index{Entity}Service`
  - `Show{Entity}Service` ou `Detail{Entity}Service`
- Cada service recebe o Repository via constructor injection
- Use DB::transaction() para operações de escrita
- Use Log::channel() se o projeto tiver channel customizado
- **Padrão A:** em `app/Packages/{Module}/Services/`
- **Padrão B:** em `app/Services/{Entity}/`

### 3.7 FormRequest
- `Store{Entity}Request` — validação para criação
- `Update{Entity}Request` — validação para atualização
- `Index{Entity}Request` — validação de filtros/paginação (se o projeto usar)
- Inclua `authorize()` retornando `true` (ou lógica de policy se o projeto usar)
- Inclua `rules()` com validações adequadas ao tipo de cada campo
- Inclua `attributes()` em pt-BR se o projeto usar localização
- **Padrão A:** em `app/Packages/{Module}/Requests/`
- **Padrão B:** em `app/Http/Requests/{Entity}/`

### 3.8 Resource
- `{Entity}Resource` — transformação para JSON
- Use `whenLoaded()` para relationships opcionais
- Formate datas como ISO 8601 se o projeto seguir esse padrão
- **Padrão A:** em `app/Packages/{Module}/Resources/`
- **Padrão B:** em `app/Http/Resources/`

### 3.9 Controller
- Extenda o BaseController do projeto
- Métodos: `index`, `store`, `show`, `update`, `destroy`
- Cada método: recebe Request → chama Service → retorna Resource
- NÃO coloque lógica de negócio no controller
- Use response helpers do projeto (trait Response, etc.)
- **Padrão A:** em `app/Packages/{Module}/Controllers/{Entity}Controller.php`
- **Padrão B:** em `app/Http/Controllers/Api/V1/{Entity}Controller.php`
- Se `--admin`: coloque em subdiretório `Admin/` e adicione middleware de permissão em cada método

### 3.10 Rotas
- Adicione as rotas em `routes/api.php` (ou no arquivo de rotas do package no Padrão A)
- Use `apiResource` ou rotas individuais seguindo o padrão existente
- Aplique middleware de autenticação conforme o projeto (Sanctum, custom auth)
- Mantenha o prefixo de versão (v1)
- Se `--admin`: use prefixo `/v1/admin/{entities}` e aplique middleware de permissão (`permission:admin.{entity}.*` ou equivalente)

### 3.11 Testes
- Gere um Feature test para os endpoints
- Gere um Unit test para o Service principal
- Use Pest (não PHPUnit raw)
- Use a Factory criada no passo 3.3
- Siga o padrão de testes existente no projeto
- **Autenticação nos testes:** Detecte o mecanismo de auth do projeto:
  - **Sanctum:** use `actingAs($user)` ou `Sanctum::actingAs($user)`
  - **Auth customizada (tokens + stored procedures):** crie um helper ou trait de teste que gera um token válido e adiciona ao header (`Authorization: Bearer {token}`). Verifique se já existe um helper assim no projeto em `tests/` antes de criar um novo
- **Docker:** Se o projeto roda em Docker, use os comandos do Makefile para rodar testes (ex: `make pest`) em vez de chamar `vendor/bin/pest` diretamente

## Passo 4: Resumo

Ao final, apresente uma lista de todos os arquivos criados e o que cada um faz.

## Passo 5: Registrar dependências

Após gerar os arquivos, verifique e atualize os registros necessários:

1. **Padrão A (Packages):** Verifique se o package tem um ServiceProvider e se precisa registrar o Repository binding nele
2. **Padrão B (DDD):** Adicione o binding `Interface → EloquentImplementation` no `RepositoryServiceProvider`
3. **Rotas:** Verifique se as rotas foram adicionadas corretamente e não conflitam com existentes

## Regras importantes

- NUNCA coloque lógica de negócio em Controllers — apenas validate, call service, return response
- NUNCA faça queries direto em Services — use o Repository
- NUNCA retorne Models direto do Controller — use Resources
- SEMPRE siga o naming exato do projeto (verifique singular/plural, prefixos, sufixos)
- SEMPRE verifique se já existe um arquivo similar antes de criar (evite duplicatas)
- Se o projeto usar Enums para status, crie o Enum correspondente
- Se `--admin`, SEMPRE adicione middleware de permissão nos endpoints e inclua filtros avançados no index (status, data, busca por nome/CPF)
- NUNCA hardcode valores de negócio (limites, prazos, tamanhos de paginação, textos). Use `config()` com fallback ou tabela settings. Ex: `config('app_name.pagination.per_page', 15)` em vez de `->paginate(15)`
