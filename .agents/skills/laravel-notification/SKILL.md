---
name: laravel-notification
description: "Scaffold de notificação Laravel com suporte a FCM (Firebase Cloud Messaging), e-mail e database. Gera Notification class, Job assíncrono, trigger no Service e teste. Use para criar notificações push, e-mail ou in-app"
user-invocable: true
allowed-tools: Read, Write, Edit, Grep, Glob, Bash, Agent
argument-hint: "[NotificationName] [--channels=fcm,mail,database] [--event=ModelAction] [--recipients=user,passengers,driver] [--queue]"
---

# Laravel Notification Generator

Gera uma notificação completa com canal de entrega, Job para processamento assíncrono, e integração com o Service que dispara o evento.

Exemplos de uso:
```
/laravel-notification RegistrationApproved --channels=fcm --event=ApproveRegistration --recipients=user
/laravel-notification RouteCreated --channels=fcm --event=CreateRoute --recipients=passengers,driver --queue
/laravel-notification RouteCancelled --channels=fcm --event=CancelRoute --recipients=passengers,driver
/laravel-notification DepartureReminder --channels=fcm --event=scheduled --recipients=passenger
/laravel-notification TripConfirmed --channels=fcm,mail --event=ConfirmTrip --recipients=passenger --queue
```

## Passo 1: Analisar o projeto

1. Leia o `CLAUDE.md` do projeto (se existir)
2. Identifique como o projeto lida com notificações atualmente:

```bash
# Verificar se já usa Laravel Notifications
Glob: app/Notifications/**/*.php
# Verificar se tem Firebase/FCM configurado
Grep: "firebase|fcm|kreait" composer.json
Grep: "FIREBASE|FCM" .env.example
# Verificar se tem device tokens
Grep: "device_token|fcm_token|push_token" app/Models/
# Verificar Jobs existentes
Glob: app/Jobs/**/*.php
# Verificar canal de notificação customizado
Glob: app/Channels/**/*.php
```

3. Identifique o padrão arquitetural (Packages, DDD, Padrão)
4. Verifique se existe um `NotificationService` ou padrão centralizado de envio

## Passo 2: Interpretar os argumentos

- `$0` — Nome da notificação (ex: `RegistrationApproved`, `RouteCreated`, `DepartureReminder`)
- `--channels=` — Canais de entrega: `fcm` (push), `mail` (e-mail), `database` (in-app). Default: `fcm`
- `--event=` — Evento que dispara a notificação (nome do Service ou `scheduled` para cron)
- `--recipients=` — Quem recebe: `user`, `passenger`, `passengers`, `driver`, `admin`. Default: `user`
- `--queue` — Se presente, processa de forma assíncrona via Laravel Queue

## Passo 3: Gerar os arquivos

### 3.1 Migration para Device Tokens (se não existir)

Verifique se já existe uma tabela de device tokens. Se não:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('token');
            $table->enum('platform', ['android', 'ios', 'web'])->default('android');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'token']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
```

### 3.2 Model DeviceToken (se não existir)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceToken extends Model
{
    protected $fillable = ['user_id', 'token', 'platform', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

### 3.3 Endpoint para registrar device token (se não existir)

Verifique se já existe. Se não, crie:
- `POST /v1/devices` — registra/atualiza token
- `DELETE /v1/devices/{token}` — remove token (logout)

### 3.4 Canal FCM (se não existir e --channels inclui fcm)

Verifique se já existe um canal FCM. Se não:

```php
<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmChannel
{
    /**
     * Envia a notificação via Firebase Cloud Messaging.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        $data = $notification->toFcm($notifiable);

        if (empty($data)) {
            return;
        }

        $tokens = $notifiable->deviceTokens()
            ->where('is_active', true)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            Log::channel('{project_channel}')->info('FCM: nenhum device token ativo', [
                'user_id' => $notifiable->id,
                'notification' => class_basename($notification),
            ]);
            return;
        }

        foreach ($tokens as $token) {
            $this->sendToToken($token, $data, $notifiable, $notification);
        }
    }

    private function sendToToken(string $token, array $data, object $notifiable, Notification $notification): void
    {
        try {
            // Usar Firebase Admin SDK (kreait/laravel-firebase) se disponível
            // Ou HTTP direto para FCM v1 API
            $response = Http::withToken($this->getAccessToken())
                ->post('https://fcm.googleapis.com/v1/projects/' . config('services.firebase.project_id') . '/messages:send', [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $data['title'],
                            'body' => $data['body'],
                        ],
                        'data' => $data['data'] ?? [],
                    ],
                ]);

            if ($response->failed()) {
                Log::channel('{project_channel}')->warning('FCM: falha no envio', [
                    'user_id' => $notifiable->id,
                    'token' => substr($token, 0, 20) . '...',
                    'status' => $response->status(),
                ]);

                // Se token inválido, desativar
                if ($response->status() === 404 || $response->status() === 410) {
                    $notifiable->deviceTokens()->where('token', $token)->update(['is_active' => false]);
                }
            }
        } catch (\Exception $e) {
            Log::channel('{project_channel}')->error('FCM: erro no envio', [
                'user_id' => $notifiable->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function getAccessToken(): string
    {
        // Implementar obtenção de OAuth2 token para Firebase
        // Usar kreait/laravel-firebase se disponível
        return app('firebase.messaging')->getAccessToken();
    }
}
```

**IMPORTANTE:** Antes de gerar o canal FCM, verifique:
- Se o projeto usa `kreait/laravel-firebase` → use a abstração do pacote
- Se o projeto usa HTTP direto → implemente o canal manual
- Se já existe uma abstração/service de push → reutilize

**Localização:** `app/Channels/FcmChannel.php`

### 3.5 Notification Class

```php
<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class {NotificationName}Notification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly {Model} $entity,
        private readonly array $extraData = [],
    ) {}

    /**
     * Canais de entrega.
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        // FCM (push)
        if ($notifiable->deviceTokens()->where('is_active', true)->exists()) {
            $channels[] = FcmChannel::class;
        }

        // Mail
        // $channels[] = 'mail';

        // Database (in-app)
        // $channels[] = 'database';

        return $channels;
    }

    /**
     * Dados para push notification (FCM).
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => '{Título da notificação}',
            'body' => '{Corpo da notificação com dados dinâmicos}',
            'data' => [
                'type' => '{notification_type}',
                '{entity}_id' => (string) $this->entity->id,
                'action' => '{action}',
                // Dados para deep linking no app
            ],
        ];
    }

    /**
     * Dados para e-mail (se --channels inclui mail).
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('{Assunto do e-mail}')
            ->greeting('Olá, ' . $notifiable->name)
            ->line('{Linha principal do e-mail}')
            ->action('{Texto do botão}', url('/'))
            ->line('{Linha final}');
    }

    /**
     * Dados para armazenamento no banco (se --channels inclui database).
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => '{notification_type}',
            '{entity}_id' => $this->entity->id,
            'message' => '{Mensagem da notificação}',
            'data' => $this->extraData,
        ];
    }
}
```

**Localização:**
- **Padrão A:** `app/Packages/{Module}/Notifications/{NotificationName}Notification.php`
- **Padrão B/C:** `app/Notifications/{NotificationName}Notification.php`

**Regras da Notification:**
- Se `--queue` → implemente `ShouldQueue` (já incluído no template)
- Se não `--queue` → remova `implements ShouldQueue` e `use Queueable`
- O título e corpo devem ser em pt-BR (adaptar ao idioma do projeto)
- Inclua dados de deep linking no `data` do FCM para o app mobile navegar à tela correta
- Use dados dinâmicos da entidade para personalizar a mensagem

### 3.6 Integração no Service que dispara

Encontre o Service que corresponde ao `--event` e adicione o disparo da notificação:

```php
// Dentro do Service execute(), após a ação principal:

// Notificar destinatários
$entity->{recipient}->notify(new {NotificationName}Notification($entity));

// Se múltiplos destinatários (ex: passengers)
Notification::send(
    $entity->passengers->map->user,
    new {NotificationName}Notification($entity)
);
```

**IMPORTANTE:**
- Se o Service estiver dentro de `DB::transaction()`, considere disparar a notificação APÓS o commit usando `DB::afterCommit()` para evitar notificar sobre algo que pode ser revertido
- Alternativa: usar Events/Listeners para desacoplar

```php
// Opção com afterCommit (preferível)
DB::transaction(function () use ($entity, $data) {
    // ... ação principal ...
});

// Notificar fora da transaction
$entity->{recipient}->notify(new {NotificationName}Notification($entity));
```

### 3.7 Teste

```php
<?php

use App\Notifications\{NotificationName}Notification;
use App\Models\{Model};
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('{NotificationName} Notification', function () {

    it('é enviada ao {recipient} quando {event}', function () {
        Notification::fake();

        $user = User::factory()->withPermission('admin.{entity}.{action}')->create();
        $entity = {Model}::factory()->create(['status' => '{from_status}']);
        // Setup: recipient com device token ativo

        // Act: executar a ação que dispara a notificação
        $this->actingAs($user)
            ->postJson("/api/v1/admin/{entities}/{$entity->id}/{action}", [
                // dados válidos
            ]);

        // Assert: notificação enviada
        Notification::assertSentTo(
            $entity->{recipient_relation},
            {NotificationName}Notification::class,
            function ($notification, $channels) {
                // Verificar que FCM está nos canais
                return in_array(\App\Channels\FcmChannel::class, $channels);
            }
        );
    });

    it('não envia se {recipient} não tem device token ativo', function () {
        Notification::fake();

        $user = User::factory()->withPermission('admin.{entity}.{action}')->create();
        $entity = {Model}::factory()->create(['status' => '{from_status}']);
        // NÃO criar device token

        $this->actingAs($user)
            ->postJson("/api/v1/admin/{entities}/{$entity->id}/{action}", [
                // dados válidos
            ]);

        Notification::assertSentTo(
            $entity->{recipient_relation},
            {NotificationName}Notification::class,
            function ($notification, $channels) {
                // FCM NÃO deve estar nos canais (sem token)
                return !in_array(\App\Channels\FcmChannel::class, $channels);
            }
        );
    });

    it('contém dados corretos no payload FCM', function () {
        $entity = {Model}::factory()->create();
        $notification = new {NotificationName}Notification($entity);
        $user = User::factory()->create();

        $fcmData = $notification->toFcm($user);

        expect($fcmData)
            ->toHaveKeys(['title', 'body', 'data'])
            ->and($fcmData['data']['{entity}_id'])->toBe((string) $entity->id)
            ->and($fcmData['data']['type'])->toBe('{notification_type}');
    });

    it('é processada na fila', function () {
        // Se --queue
        $entity = {Model}::factory()->create();
        $notification = new {NotificationName}Notification($entity);

        expect($notification)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
    });
});
```

**Localização:** `tests/Feature/Notifications/{NotificationName}NotificationTest.php`

## Passo 4: Scheduled notifications (se --event=scheduled)

Se a notificação é agendada (como lembrete 15min antes, ou notificação às 17h):

### 4.1 Command

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Send{NotificationName}Command extends Command
{
    protected $signature = '{entity}:send-{notification-name}';
    protected $description = '{Descrição do comando}';

    public function handle(): int
    {
        // Buscar entidades elegíveis
        // Ex: rotas de amanhã que ainda não receberam notificação das 17h
        $entities = {Model}::query()
            ->where('date', now()->addDay()->toDateString())
            ->where('{notification_sent_flag}', false)
            ->get();

        foreach ($entities as $entity) {
            // Notificar cada destinatário
            // Marcar como notificado
        }

        $this->info("Enviadas {$entities->count()} notificações.");
        return Command::SUCCESS;
    }
}
```

### 4.2 Scheduler

Adicione no `app/Console/Kernel.php` (ou `routes/console.php` no Laravel 11+):

```php
Schedule::command('{entity}:send-{notification-name}')
    ->dailyAt('17:00')  // ou ->everyFifteenMinutes() para lembretes
    ->withoutOverlapping();
```

## Passo 5: Resumo

Ao final, apresente:
1. Lista de arquivos criados/modificados
2. Canal de entrega configurado
3. Trigger: qual Service/Command dispara a notificação
4. Payload: título e corpo da notificação
5. Se criou infraestrutura (DeviceToken, FcmChannel), listar os passos de setup:
   - Configurar Firebase credentials no `.env`
   - Rodar migration
   - Registrar endpoint de device token

## Regras importantes

- NUNCA dispare notificações dentro de `DB::transaction()` — use `DB::afterCommit()` ou dispare após
- SEMPRE inclua dados de deep linking no payload FCM para o app mobile navegar corretamente
- SEMPRE use fila (`ShouldQueue`) para notificações que podem ser lentas (FCM, e-mail)
- SEMPRE trate token inválido/expirado desativando o device token
- SEMPRE logue falhas de envio mas NÃO lance exceção (notificação é side effect, não deve quebrar o fluxo principal)
- Se o projeto já tem um padrão de notificações, SIGA-O em vez de criar novo
- Textos de notificação devem ser em pt-BR (ou no idioma do projeto)
- Verifique se `kreait/laravel-firebase` está no composer.json antes de implementar FCM manualmente
- NUNCA hardcode textos de notificação no código — use lang files (`__('notifications.route_created')`) ou config. Títulos e corpos devem ser configuráveis
- NUNCA hardcode horários de agendamento (ex: "17h") — use `config('vivamobil.notifications.departure_reminder_time', '17:00')` no scheduler
- Intervalos de lembretes (ex: 15min antes) devem vir de config, não de literais
