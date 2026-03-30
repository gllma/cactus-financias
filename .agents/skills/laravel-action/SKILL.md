---
name: laravel-action
description: "Criar endpoint de ação/transição de estado Laravel: Service com validação de estado, FormRequest, rota com permissão, e teste de transição. Use para ações como approve, reject, cancel, finish — endpoints single-purpose que mudam o estado de uma entidade"
user-invocable: true
allowed-tools: Read, Write, Edit, Grep, Glob, Bash, Agent
argument-hint: "[ActionName] [--model=ModelName] [--from=status_origem] [--to=status_destino] [--fields=motivo:required,observacao:optional] [--notify=recipients]"
---

# Laravel Action Generator

Gera um endpoint de ação (transição de estado) completo. Diferente do CRUD que gera operações genéricas de dados, o Action gera **um endpoint single-purpose** que muda o estado de uma entidade com validações, side effects e permissões.

Exemplos de uso:
```
/laravel-action ApproveRegistration --model=User --from=pending --to=approved --notify=user
/laravel-action RejectTripRequest --model=TripBooking --from=pending --to=rejected --fields=motivo:required,observacao:optional
/laravel-action CancelRoute --model=Route --from=active --to=cancelled --fields=motivo:required --notify=passengers,driver
/laravel-action FinishRoute --model=Route --from=in_progress --to=completed
```

## Passo 1: Detectar o padrão arquitetural

1. Leia o `CLAUDE.md` do projeto (se existir)
2. Identifique o padrão arquitetural (mesmo critério do laravel-crud):
   - **Padrão A — Modular (Packages):** `app/Packages/` existe
   - **Padrão B — DDD com Repository Interface:** `app/Repositories/Contracts/` existe
   - **Padrão C — Laravel Padrão:** nenhum dos acima
3. Analise 1 endpoint de ação existente no projeto como referência (procure por `approve`, `reject`, `cancel`, `finish` nos controllers)
4. Identifique como o projeto lida com:
   - **Enums de status:** Procure em `app/Enums/` ou dentro dos packages
   - **Permissões:** Middleware? Policy? Stored procedure?
   - **Notificações:** Laravel Notification? Job? Evento? FCM direto?
   - **Auditoria:** O projeto loga transições? Como?

## Passo 2: Interpretar os argumentos

- `$0` — Nome da ação no formato `{Verb}{Entity}` (ex: `ApproveRegistration`, `CancelRoute`)
- `--model=` — Model alvo (obrigatório). Se não informado, extraia do nome da ação
- `--from=` — Status de origem permitido (pode ser múltiplo separado por vírgula: `pending,adjusted`)
- `--to=` — Status de destino após a ação
- `--fields=` — Campos adicionais da request (ex: `motivo:required,observacao:optional`)
- `--notify=` — Quem notificar após a ação (ex: `user`, `passengers,driver`, `admin`)

- `--cascade=` (opcional) — Entidades vinculadas que devem transicionar junto (ex: `--cascade=return_ticket` para rejeitar ticket de volta ao rejeitar o de ida)

Se `--from` e `--to` não forem informados, analise o Model e seus Enums para sugerir as transições.

**IMPORTANTE: Antes de gerar, consulte as regras de negócio:**
1. Verifique `docs/` no projeto por documentação de regras de negócio
2. Leia o `CLAUDE.md` do projeto por regras específicas
3. Procure por comentários no Model ou Enum que documentem transições permitidas
4. Exemplos de regras que afetam ações no vivamobil:
   - RN15: Tickets de ida e volta são vinculados — rejeitar um obriga rejeitar o outro
   - RN11: Cancelamento só permitido até 12h antes do horário da rota
   - RN26: Rota só pode ser finalizada após TODAS as paradas processadas
   - RN17: Cancelamento de rota é irreversível e solicitações voltam para pendente

## Passo 3: Gerar os arquivos

### 3.1 Enum de Status (se não existir)

Verifique se já existe um Enum para o status do Model. Se não existir, crie:

```php
<?php

namespace App\Enums; // ou dentro do Package

enum {Model}StatusEnum: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    // ... demais status baseados no ciclo de vida da entidade

    /**
     * Transições permitidas a partir deste estado.
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::APPROVED, self::REJECTED],
            self::APPROVED => [self::CANCELLED],
            self::REJECTED => [], // estado final
            default => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions());
    }
}
```

**Localização:**
- **Padrão A:** `app/Packages/{Module}/Enums/{Model}StatusEnum.php`
- **Padrão B:** `app/Enums/{Model}StatusEnum.php`

### 3.2 FormRequest

```php
<?php

namespace App\Http\Requests\{Entity}; // ajustar conforme padrão

use Illuminate\Foundation\Http\FormRequest;

class {Action}{Entity}Request extends FormRequest
{
    public function authorize(): bool
    {
        // Usar sistema de permissão do projeto
        return true; // ou $this->user()->hasPermission('admin.{entity}.{action}')
    }

    public function rules(): array
    {
        return [
            // Campos do --fields
            // 'motivo' => ['required', 'string', 'max:500'],
            // 'observacao' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
```

**Localização:**
- **Padrão A:** `app/Packages/{Module}/Requests/{Action}{Entity}Request.php`
- **Padrão B:** `app/Http/Requests/{Entity}/{Action}{Entity}Request.php`

### 3.3 Service (núcleo da ação)

```php
<?php

namespace App\Services\{Entity}; // ajustar conforme padrão

use App\Models\{Model};
use App\Enums\{Model}StatusEnum;
use App\Repositories\Contracts\{Model}RepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class {Action}{Entity}Service
{
    public function __construct(
        private readonly {Model}RepositoryInterface $repository,
        // Injetar NotificationService se --notify foi passado
    ) {}

    public function execute(int|string $id, array $data): {Model}
    {
        return DB::transaction(function () use ($id, $data) {
            // 1. Buscar a entidade (com lock para evitar race condition)
            $entity = $this->repository->findOrFail($id);
            // Alternativa com lock: {Model}::where('id', $id)->lockForUpdate()->firstOrFail();

            // 2. Validar transição de estado
            $currentStatus = $entity->status;
            $targetStatus = {Model}StatusEnum::{TO_STATUS};

            if (!$currentStatus->canTransitionTo($targetStatus)) {
                throw new \DomainException(
                    "Não é possível {action_verb} {entity_name} com status '{$currentStatus->value}'. " .
                    "Status permitidos: " . implode(', ', array_map(fn($s) => $s->value, $currentStatus->allowedTransitions()))
                );
            }

            // 3. Executar a transição
            $entity->status = $targetStatus;
            // Preencher campos adicionais (motivo, observação, etc.)
            // $entity->rejection_reason = $data['motivo'] ?? null;
            $entity->save();

            // 4. Side effects
            // Logging
            Log::channel('{project_channel}')->info('{Action} {entity}', [
                '{entity}_id' => $entity->id,
                'from_status' => $currentStatus->value,
                'to_status' => $targetStatus->value,
                'user_id' => auth()->id(),
                // dados adicionais relevantes
            ]);

            // Notificações (se --notify)
            // $this->notificationService->notify{Action}($entity);

            return $entity->fresh();
        });
    }
}
```

**Localização:**
- **Padrão A:** `app/Packages/{Module}/Services/{Action}{Entity}Service.php`
- **Padrão B:** `app/Services/{Entity}/{Action}{Entity}Service.php`

**Regras do Service:**
- SEMPRE use `DB::transaction()` para garantir atomicidade
- SEMPRE valide a transição de estado antes de executar
- SEMPRE logue a transição com contexto completo
- Use `lockForUpdate()` se houver risco de race condition
- Dispare notificações APÓS a transaction (use `DB::afterCommit()` ou dispare fora do closure)
- Lance `DomainException` (ou exceção customizada do projeto) para transições inválidas

**Transições em cascata (se `--cascade` ou se regra de negócio exigir):**

Quando a ação numa entidade deve afetar entidades vinculadas, trate DENTRO da mesma transaction:

```php
// Exemplo: rejeitar ticket de ida obriga rejeitar ticket de volta (RN15)
return DB::transaction(function () use ($id, $data) {
    $entity = $this->repository->findOrFail($id);

    // Transição principal
    $entity->status = {Model}StatusEnum::REJECTED;
    $entity->rejection_reason = $data['motivo'];
    $entity->save();

    // Transição em cascata (entidade vinculada)
    if ($entity->linked_ticket_id) {
        $linkedTicket = $this->repository->findOrFail($entity->linked_ticket_id);
        $linkedTicket->status = {Model}StatusEnum::REJECTED;
        $linkedTicket->rejection_reason = $data['motivo'];
        $linkedTicket->save();
    }

    return $entity->fresh();
});
```

**Validações temporais:**

Se a ação tiver restrição de tempo (ex: cancelamento só até 12h antes), valide no Service:

```php
// Exemplo: cancelamento até 12h antes da rota
if ($entity->route && $entity->route->departure_time->diffInHours(now()) < 12) {
    throw new \DomainException(
        'Cancelamento permitido até 12h antes do horário da rota. Para urgências, entre em contato com a central.'
    );
}
```

### 3.4 Rota

Adicione a rota no arquivo de rotas do projeto:

```php
// Em routes/api.php ou no arquivo de rotas do package
Route::post('/v1/admin/{entities}/{id}/{action}', [{Entity}Controller::class, '{action}'])
    ->middleware(['auth', 'permission:admin.{entity}.{action}'])
    ->name('admin.{entities}.{action}');
```

**Convenções de URL:**
- Ação sobre uma entidade: `POST /v1/{entities}/{id}/{action}` (ex: `POST /v1/admin/registrations/5/approve`)
- Ação sem entidade: `POST /v1/{entities}/{action}` (ex: `POST /v1/admin/trip-requests/bulk-reject`)
- SEMPRE use `POST` para ações que alteram estado (nunca GET)
- Use prefixo `admin/` para endpoints administrativos

### 3.5 Método no Controller

Adicione o método no Controller existente (ou crie um novo se não existir):

```php
public function {action}({Action}{Entity}Request $request, int $id): JsonResponse
{
    $result = $this->{action}{Entity}Service->execute($id, $request->validated());

    return response()->json([
        'message' => '{Entity} {action_past_tense} com sucesso.',
        'data' => new {Entity}Resource($result),
    ]);
}
```

**Regras do Controller:**
- O controller APENAS recebe o request, chama o service, e retorna o response
- Validação de dados → FormRequest
- Validação de estado → Service
- NÃO capturar exceções no controller (deixe o handler global tratar)

### 3.6 Teste

Gere testes cobrindo todos os cenários:

```php
<?php

use App\Models\{Model};
use App\Models\User;
use App\Enums\{Model}StatusEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('{Action} {Entity}', function () {

    // === TRANSIÇÃO VÁLIDA ===
    it('{action_verb} {entity} com status {from}', function () {
        $user = User::factory()->withPermission('admin.{entity}.{action}')->create();
        $entity = {Model}::factory()->create(['status' => {Model}StatusEnum::{FROM}]);

        // NOTA: Detectar mecanismo de auth do projeto:
        // - Sanctum: $this->actingAs($user)->postJson(...)
        // - Auth customizada (tokens): usar helper de teste que gera token válido
        //   Ex: $this->withToken(createTestToken($user))->postJson(...)
        $response = $this->actingAs($user)
            ->postJson("/api/v1/admin/{entities}/{$entity->id}/{action}", [
                // campos do --fields
            ]);

        $response->assertOk();
        expect($entity->fresh()->status)->toBe({Model}StatusEnum::{TO});
    });

    // === TRANSIÇÃO INVÁLIDA ===
    it('rejeita {action} quando status é {invalid_status}', function () {
        $user = User::factory()->withPermission('admin.{entity}.{action}')->create();
        $entity = {Model}::factory()->create(['status' => {Model}StatusEnum::{INVALID}]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/admin/{entities}/{$entity->id}/{action}");

        $response->assertUnprocessable(); // ou 409 Conflict
    });

    // === VALIDAÇÃO DE CAMPOS ===
    it('requer motivo para {action}', function () {
        // Se --fields incluiu campos required
        $user = User::factory()->withPermission('admin.{entity}.{action}')->create();
        $entity = {Model}::factory()->create(['status' => {Model}StatusEnum::{FROM}]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/admin/{entities}/{$entity->id}/{action}", [
                // sem motivo
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['motivo']);
    });

    // === PERMISSÃO ===
    it('retorna 403 sem permissão de {action}', function () {
        $user = User::factory()->create(); // sem permissão
        $entity = {Model}::factory()->create(['status' => {Model}StatusEnum::{FROM}]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/admin/{entities}/{$entity->id}/{action}");

        $response->assertForbidden();
    });

    // === AUTENTICAÇÃO ===
    it('retorna 401 sem autenticação', function () {
        $entity = {Model}::factory()->create();

        $response = $this->postJson("/api/v1/admin/{entities}/{$entity->id}/{action}");

        $response->assertUnauthorized();
    });

    // === ENTIDADE NÃO ENCONTRADA ===
    it('retorna 404 para {entity} inexistente', function () {
        $user = User::factory()->withPermission('admin.{entity}.{action}')->create();

        $response = $this->actingAs($user)
            ->postJson("/api/v1/admin/{entities}/99999/{action}");

        $response->assertNotFound();
    });

    // === TRANSIÇÃO EM CASCATA (se --cascade ou regra de negócio) ===
    it('{action_verb} entidade vinculada junto com a principal', function () {
        $user = User::factory()->withPermission('admin.{entity}.{action}')->create();
        $entity = {Model}::factory()->create(['status' => {Model}StatusEnum::{FROM}]);
        $linked = {Model}::factory()->create([
            'status' => {Model}StatusEnum::{FROM},
            'linked_{entity}_id' => $entity->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/admin/{entities}/{$entity->id}/{action}", [
                // dados válidos
            ]);

        $response->assertOk();
        expect($entity->fresh()->status)->toBe({Model}StatusEnum::{TO});
        expect($linked->fresh()->status)->toBe({Model}StatusEnum::{TO});
    });

    // === VALIDAÇÃO TEMPORAL (se regra de negócio exigir) ===
    it('bloqueia {action} fora do prazo permitido', function () {
        $user = User::factory()->withPermission('admin.{entity}.{action}')->create();
        // Criar entidade com horário próximo (menos de 12h)
        $entity = {Model}::factory()->create([
            'status' => {Model}StatusEnum::{FROM},
            'scheduled_at' => now()->addHours(6), // dentro do prazo bloqueado
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/admin/{entities}/{$entity->id}/{action}");

        $response->assertUnprocessable(); // ou 409 Conflict
    });

    // === NOTIFICAÇÃO (se --notify) ===
    it('envia notificação para {recipients} ao {action}', function () {
        Notification::fake();
        $user = User::factory()->withPermission('admin.{entity}.{action}')->create();
        $entity = {Model}::factory()->create(['status' => {Model}StatusEnum::{FROM}]);

        $this->actingAs($user)
            ->postJson("/api/v1/admin/{entities}/{$entity->id}/{action}", [
                // dados válidos
            ]);

        Notification::assertSentTo(
            $entity->{recipient_relation},
            {Action}{Entity}Notification::class
        );
    });
});
```

**Localização:**
- **Feature test:** `tests/Feature/Admin/{Action}{Entity}Test.php`
- **Unit test (service):** `tests/Unit/Services/{Entity}/{Action}{Entity}ServiceTest.php`

## Passo 4: Resumo

Ao final, apresente:
1. Lista de todos os arquivos criados
2. Mapa da transição implementada: `{from} → {action} → {to}`
3. Side effects configurados (notificações, logs)
4. Instruções para registrar a permissão no seeder (se aplicável)

## Regras importantes

- SEMPRE valide a transição de estado no Service (nunca no Controller)
- SEMPRE use `DB::transaction()` para ações que alteram estado
- SEMPRE gere testes para: transição válida, transição inválida, validação de campos, permissão, auth, 404
- SEMPRE use POST para ações que alteram estado
- NUNCA permita transições de estado no Controller (lógica vai no Service)
- Se `--notify` foi passado, configure o disparo de notificação dentro do Service
- Se o Model já tiver Enum de status com `allowedTransitions()`, use-o. Se não, crie
- Se existirem múltiplos estados de origem (`--from=pending,adjusted`), trate todos no Service
- Prefira `DomainException` ou exceção customizada do projeto para erros de regra de negócio
- NUNCA hardcode valores de negócio no Service — prazos, limites, thresholds devem vir de `config()` ou tabela settings. Ex: `config('vivamobil.cancellation_deadline_hours', 12)` em vez de `< 12`
- Mensagens de erro/sucesso devem usar lang files ou config, não strings literais
