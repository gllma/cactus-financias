---
name: laravel-test
description: "Gerar testes Pest (Feature e Unit) para controllers, services e repositories Laravel seguindo os padrões do projeto"
user-invocable: true
allowed-tools: Read, Write, Edit, Grep, Glob, Bash, Agent
argument-hint: "[ClasseName] [--type=feature|unit|both] [--methods=index,store,show]"
---

# Laravel Test Generator

Gera testes usando Pest seguindo os padrões do projeto atual.

## Passo 1: Entender o contexto

1. Leia o `CLAUDE.md` do projeto (se existir)
2. Analise a estrutura de testes existente:
   - Verifique `tests/Feature/` e `tests/Unit/` para entender o padrão
   - Identifique se usam Pest puro, Pest + Laravel plugin, ou PHPUnit
   - Verifique quais traits são usados (RefreshDatabase, WithFaker, etc.)
   - Veja como factories são usadas nos testes existentes
3. Leia `phpunit.xml` para entender a configuração de test suites e environment

## Passo 2: Interpretar os argumentos

- `$0` — Nome da classe a testar (ex: `ContractController`, `CreateContractService`, `Contract`)
- `--type=` — Tipo de teste: `feature` (endpoints), `unit` (lógica), `both` (ambos). Default: detectar pelo tipo da classe
- `--methods=` — Métodos específicos a testar. Default: todos os públicos

**Detecção automática de tipo:**
- Se o nome terminar em `Controller` → Feature test (testa endpoints HTTP)
- Se o nome terminar em `Service` → Unit test (testa lógica de negócio)
- Se o nome terminar em `Repository` → Unit test (testa queries)
- Se for um nome de Model → ambos (feature para endpoints, unit para scopes/relationships)

## Passo 3: Localizar a classe alvo

1. Use Glob para encontrar o arquivo da classe
2. Leia o arquivo completo para entender:
   - Métodos públicos disponíveis
   - Dependências (injeção no constructor)
   - Models envolvidos
   - Validações (FormRequests)
   - Responses esperadas (Resources)

## Passo 4: Localizar dependências para o teste

1. Encontre e leia as Factories dos Models envolvidos
2. Encontre as rotas relacionadas (para Feature tests)
3. Encontre os FormRequests (para testar validação)
4. Encontre os Resources (para testar estrutura da response)

## Passo 5: Gerar Feature Test (endpoints HTTP)

Local: `tests/Feature/{Entity}/` ou `tests/Feature/` (siga o padrão do projeto)

```php
<?php

use App\Models\{Entity};
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Setup: criar usuário autenticado e dados necessários
});

describe('{Entity} API', function () {

    describe('GET /api/v1/{entities}', function () {
        it('lista {entities} com paginação', function () {
            // Arrange: criar registros via factory
            // Act: fazer request autenticado
            // Assert: verificar status 200, estrutura JSON, paginação
        });

        it('filtra {entities} por {campo}', function () {
            // Testar filtros se o endpoint suportar
        });

        it('retorna 401 sem autenticação', function () {
            // Testar que endpoint é protegido
        });
    });

    describe('POST /api/v1/{entities}', function () {
        it('cria {entity} com dados válidos', function () {
            // Arrange: dados válidos
            // Act: POST request
            // Assert: status 201, dados no banco, estrutura response
        });

        it('retorna 422 com dados inválidos', function () {
            // Testar cada regra de validação relevante
        });
    });

    describe('GET /api/v1/{entities}/{id}', function () {
        it('retorna {entity} por id', function () {
            // Arrange: criar registro
            // Act: GET request
            // Assert: status 200, dados corretos
        });

        it('retorna 404 para id inexistente', function () {
            // Testar com UUID/id inválido
        });
    });

    describe('PUT /api/v1/{entities}/{id}', function () {
        it('atualiza {entity} com dados válidos', function () {
            // Arrange: criar registro + dados de update
            // Act: PUT request
            // Assert: status 200, dados atualizados no banco
        });

        it('retorna 422 com dados inválidos', function () {
            // Testar validação de update
        });
    });

    describe('DELETE /api/v1/{entities}/{id}', function () {
        it('remove {entity}', function () {
            // Arrange: criar registro
            // Act: DELETE request
            // Assert: status 200/204, registro removido (ou soft deleted)
        });
    });
});
```

### Padrões para Feature tests:

- **Autenticação — detecte o mecanismo do projeto:**
  - **Sanctum:** use `actingAs($user)` ou `Sanctum::actingAs($user, ['*'])`
  - **Auth customizada (tokens com stored procedures):** NÃO use `actingAs()`. Crie um trait de teste ou helper:
    ```php
    // tests/Traits/AuthenticatesForTesting.php
    trait AuthenticatesForTesting
    {
        protected function authenticatedHeaders(User $user): array
        {
            // Gerar token via service/repository do projeto
            $token = app(CreateTokenService::class)->execute($user);
            return ['Authorization' => "Bearer {$token->plainTextToken}"];
        }

        protected function authGet(User $user, string $uri): TestResponse
        {
            return $this->getJson($uri, $this->authenticatedHeaders($user));
        }

        protected function authPost(User $user, string $uri, array $data = []): TestResponse
        {
            return $this->postJson($uri, $data, $this->authenticatedHeaders($user));
        }
    }
    ```
    Verifique se já existe um helper/trait similar em `tests/` antes de criar
  - **Dica:** Procure nos testes existentes como a autenticação é feita. Se não há testes, analise o middleware de auth e o LoginService/LoginController
- Use `getJson()`, `postJson()`, `putJson()`, `deleteJson()` (não `get()`, `post()`)
- Verifique estrutura completa do JSON com `assertJsonStructure()`
- Verifique dados no banco com `assertDatabaseHas()` / `assertDatabaseMissing()`
- Verifique soft delete com `assertSoftDeleted()` se aplicável
- Teste paginação verificando `meta.current_page`, `meta.total`, etc.

## Passo 6: Gerar Unit Test (lógica de negócio)

Local: `tests/Unit/Services/{Entity}/` ou `tests/Unit/` (siga o padrão do projeto)

```php
<?php

use App\Services\{Entity}\Create{Entity}Service;
use App\Repositories\Contracts\{Entity}RepositoryInterface;
use App\Models\{Entity};

describe('Create{Entity}Service', function () {

    it('cria {entity} com dados válidos', function () {
        // Arrange: mock do repository, dados de entrada
        // Act: executar o service
        // Assert: verificar que o repository foi chamado corretamente
    });

    it('lança exceção quando {condição de erro}', function () {
        // Testar cenários de erro/exceção
    });
});
```

### Padrões para Unit tests:

- Mocke dependências externas (repositories, serviços externos)
- NÃO mocke o banco se o projeto preferir integration tests (verifique CLAUDE.md)
- Teste cada cenário: sucesso, validação, exceção, edge cases
- Use `expect()->toThrow()` para testar exceções no Pest
- Use `Mockery::mock()` ou `$this->mock()` conforme o padrão do projeto

## Passo 6.5: Gerar testes de State Transition (quando aplicável)

**Detecte automaticamente:** Se a classe alvo tiver métodos que mudam status/estado (approve, reject, cancel, finish, activate, etc.) ou se o Model tiver um campo `status` com Enum, gere testes de transição de estado.

Local: junto com os Unit tests do Service

```php
<?php

use App\Models\{Entity};
use App\Enums\{Entity}StatusEnum;

describe('{Action}{Entity}Service — state transitions', function () {

    // TRANSIÇÕES VÁLIDAS
    it('transiciona de {estado_origem} para {estado_destino}', function () {
        $entity = {Entity}::factory()->create(['status' => {Entity}StatusEnum::PENDING]);

        $service = app({Action}{Entity}Service::class);
        $result = $service->execute($entity->id, $validData);

        expect($result->status)->toBe({Entity}StatusEnum::APPROVED);
        // Verificar side effects: notificações, logs, etc.
    });

    // TRANSIÇÕES INVÁLIDAS
    it('rejeita transição de {estado_invalido} para {estado_destino}', function () {
        $entity = {Entity}::factory()->create(['status' => {Entity}StatusEnum::APPROVED]);

        $service = app({Action}{Entity}Service::class);

        expect(fn () => $service->execute($entity->id, $validData))
            ->toThrow(InvalidStateTransitionException::class);
    });

    // IDEMPOTÊNCIA
    it('não altera se já está no estado destino', function () {
        // Verificar que aplicar a mesma ação duas vezes não causa erro
        // ou lança exceção informativa
    });

    // PERMISSÕES
    it('rejeita ação sem permissão adequada', function () {
        // Testar que usuário sem permissão não pode executar a transição
    });

    // EFEITOS COLATERAIS
    it('dispara notificação ao transicionar para {estado}', function () {
        // Verificar que Notification/Job foi despachado
        Notification::fake();
        // ... executar ação ...
        Notification::assertSentTo(...);
    });

    it('registra log de auditoria na transição', function () {
        // Se o projeto tiver audit log
    });
});
```

### Quando gerar testes de state transition:

- Se o Model tiver campo `status` com Enum → testar todas as transições válidas e inválidas
- Se existirem Services nomeados como `Approve*`, `Reject*`, `Cancel*`, `Finish*`, `Activate*` → testar cada transição
- Sempre testar: transição válida, transição inválida (estado de origem errado), permissão negada, efeitos colaterais (notificações, logs)
- Consulte o documento de regras de negócio ou o `CLAUDE.md` para mapear quais transições são permitidas

### Diagrama de estados a mapear:

Ao encontrar state machines no projeto, monte o mapa de transições antes de gerar os testes:

```
Estado Origem → Ação → Estado Destino → Side Effects
PENDING → approve → APPROVED → notifica usuário
PENDING → reject → REJECTED → notifica usuário + motivo obrigatório
APPROVED → ✗ approve → ERRO (já aprovado)
REJECTED → ✗ approve → ERRO (estado final)
```

## Passo 7: Verificar e executar

1. Verifique que todos os imports estão corretos
2. Detecte como rodar testes neste projeto:
   - Se tem **Makefile** com target de teste → use `make pest` ou `make test`
   - Se roda em **Docker** → use o comando Docker do projeto (ex: `docker compose exec php-fpm vendor/bin/pest`)
   - Se roda **local** → use `vendor/bin/pest` ou `php artisan test`
   - Verifique `.env.testing` ou `phpunit.xml` para o banco de testes (SQLite, PostgreSQL de teste, etc.)
3. Se houver erros, corrija-os
4. Se é o **primeiro teste do projeto** (nenhum teste existente), verifique também:
   - Se `phpunit.xml` está configurado com as suites corretas
   - Se `.env.testing` existe com configuração de banco de testes
   - Se as factories dos Models dependentes existem (crie se necessário)

## Regras importantes

- SEMPRE use Pest syntax (não PHPUnit classes) — a menos que o projeto use PHPUnit
- SEMPRE use factories para criar dados de teste (nunca inserts manuais)
- SEMPRE teste autenticação (401) e validação (422) nos Feature tests
- SEMPRE use `describe()` e `it()` para organizar os testes
- NUNCA teste implementação interna — teste comportamento
- NUNCA deixe testes dependentes entre si (cada test é isolado)
- Use nomes descritivos em português para os `it()` quando o projeto usar pt-BR
- Se o projeto tiver poucos testes, use os existentes como base de estilo mas melhore a cobertura
