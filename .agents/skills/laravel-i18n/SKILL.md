---
name: laravel-i18n
description: "Implementa internacionalização completa em projetos Laravel: middleware de locale, arquivos de tradução organizados por domínio, mensagens humanizadas, refactor de hardcoded strings. Suporta detecção via Accept-Language header e múltiplos idiomas"
user-invocable: true
allowed-tools: Read, Write, Edit, Grep, Glob, Bash, Agent
argument-hint: "[--locales=pt_BR,es,en] [--default=pt_BR] [--fallback=es] [--scope=setup|refactor|add-locale]"
---

# Laravel i18n — Internacionalização

Implementa internacionalização completa seguindo boas práticas de APIs modernas.

Exemplos de uso:
```
/laravel-i18n --locales=pt_BR,es,en --default=pt_BR --fallback=es
/laravel-i18n --scope=setup
/laravel-i18n --scope=refactor
/laravel-i18n --scope=add-locale --locales=fr
```

## Princípios

1. **Client define o idioma** via header `Accept-Language` — a API respeita
2. **Mensagens humanizadas** — contextual e empática, nunca genérica
3. **Organização por domínio** — um arquivo por módulo de negócio, não um gigante `messages.php`
4. **Variáveis dinâmicas** — mensagens usam `:placeholders` para contexto
5. **Nunca hardcoded** — toda string voltada ao usuário usa `__()` ou `trans()`

## Escopo: setup

Cria toda a infraestrutura de i18n do zero.

### Passo 1: Analisar o projeto

1. Leia o `CLAUDE.md` do projeto
2. Identifique o padrão arquitetural (Packages, DDD, Padrão)
3. Levante todos os módulos/domínios do projeto para organizar os arquivos de tradução
4. Verifique o que já existe em `lang/`:
   - Arquivos de validação (geralmente já existem)
   - JSON de tradução do Laravel
   - Outros arquivos customizados

### Passo 2: Configurar locales

Atualize `config/app.php`:

```php
'locale' => env('APP_LOCALE', 'pt_BR'),
'fallback_locale' => env('APP_FALLBACK_LOCALE', 'es'),
'faker_locale' => env('APP_FAKER_LOCALE', 'pt_BR'),
```

Se o projeto tiver config customizada (ex: `config/vivamobil.php`), adicione seção de locales:

```php
'locales' => [
    'supported' => ['pt_BR', 'es', 'en'],
    'default' => env('APP_LOCALE', 'pt_BR'),
    'fallback' => env('APP_FALLBACK_LOCALE', 'es'),
],
```

### Passo 3: Criar middleware SetLocale

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    /**
     * Idiomas suportados pela aplicação.
     */
    private function supportedLocales(): array
    {
        return config('vivamobil.locales.supported', ['pt_BR', 'es', 'en']);
    }

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);

        $response = $next($request);

        // Informar ao client qual locale foi usado
        if ($response instanceof Response) {
            $response->headers->set('Content-Language', str_replace('_', '-', $locale));
        }

        return $response;
    }

    /**
     * Resolve o locale a partir do request.
     *
     * Prioridade:
     * 1. Query param ?lang=pt_BR (útil para testes)
     * 2. Header Accept-Language
     * 3. Default da aplicação
     */
    private function resolveLocale(Request $request): string
    {
        // 1. Query param (prioridade máxima, útil para debug/testes)
        if ($request->has('lang')) {
            $lang = $this->normalizeLocale($request->query('lang'));
            if ($this->isSupported($lang)) {
                return $lang;
            }
        }

        // 2. Accept-Language header
        $acceptLanguage = $request->header('Accept-Language');
        if ($acceptLanguage) {
            $locale = $this->parseAcceptLanguage($acceptLanguage);
            if ($locale) {
                return $locale;
            }
        }

        // 3. Default
        return config('vivamobil.locales.default', config('app.locale', 'pt_BR'));
    }

    /**
     * Parseia o header Accept-Language respeitando quality values.
     *
     * Exemplo: "pt-BR, es;q=0.8, en;q=0.5"
     * Resultado: pt_BR (maior q-value suportado)
     */
    private function parseAcceptLanguage(string $header): ?string
    {
        $locales = [];

        foreach (explode(',', $header) as $part) {
            $part = trim($part);
            $quality = 1.0;

            if (str_contains($part, ';q=')) {
                [$part, $q] = explode(';q=', $part);
                $quality = (float) trim($q);
            }

            $normalized = $this->normalizeLocale(trim($part));
            if ($this->isSupported($normalized)) {
                $locales[$normalized] = $quality;
            }
        }

        if (empty($locales)) {
            return null;
        }

        arsort($locales);
        return array_key_first($locales);
    }

    /**
     * Normaliza formato do locale.
     * "pt-BR" → "pt_BR", "pt" → "pt_BR", "es" → "es", "en" → "en"
     */
    private function normalizeLocale(string $locale): string
    {
        $locale = str_replace('-', '_', $locale);

        // Mapear variantes curtas para completas
        $aliases = config('vivamobil.locales.aliases', [
            'pt' => 'pt_BR',
            'pt_br' => 'pt_BR',
            'es' => 'es',
            'en' => 'en',
            'en_us' => 'en',
            'en_US' => 'en',
        ]);

        return $aliases[strtolower($locale)] ?? $aliases[$locale] ?? $locale;
    }

    private function isSupported(string $locale): bool
    {
        return in_array($locale, $this->supportedLocales());
    }
}
```

**Localização:**
- **Padrão A (Packages):** `app/Http/Middleware/SetLocaleMiddleware.php` (middleware global, não de package)
- **Padrão B/C:** `app/Http/Middleware/SetLocaleMiddleware.php`

**Registro:** Adicionar ao middleware global (todas as rotas API) no bootstrap ou Kernel:

```php
// bootstrap/app.php (Laravel 11+)
->withMiddleware(function (Middleware $middleware) {
    $middleware->api(prepend: [
        \App\Http\Middleware\SetLocaleMiddleware::class,
    ]);
})

// Ou em routes/api.php se usar middleware group
Route::middleware(['throttle:api', SetLocaleMiddleware::class])->group(function () {
    // ...
});
```

### Passo 4: Criar arquivos de tradução por domínio

Analise os módulos/packages do projeto e crie um arquivo de tradução para cada um.

**Estrutura:**
```
lang/
├── pt_BR/
│   ├── auth.php            # Autenticação, login, registro, tokens
│   ├── trip.php            # Viagens, solicitações
│   ├── route.php           # Rotas, embarque, desembarque
│   ├── address.php         # Endereços, favoritos
│   ├── user.php            # Perfil, dados pessoais
│   ├── admin.php           # Gestão administrativa
│   ├── notification.php    # Textos de push/email
│   ├── common.php          # Erros genéricos, respostas padrão
│   └── validation.php      # Validação (já existe)
├── es/
│   └── (mesmos arquivos)
├── en/
│   └── (mesmos arquivos)
```

**Formato de cada arquivo:**

```php
<?php

// lang/pt_BR/auth.php

return [

    /*
    |--------------------------------------------------------------------------
    | Autenticação
    |--------------------------------------------------------------------------
    |
    | Mensagens relacionadas a login, registro, tokens e permissões.
    |
    */

    // === Login ===
    'login_success' => 'Bem-vindo de volta, :name!',
    'login_failed' => 'CPF ou senha incorretos. Verifique seus dados e tente novamente.',
    'login_blocked' => 'Sua conta está temporariamente bloqueada. Tente novamente em :minutes minutos.',

    // === Registro ===
    'register_success' => 'Bem-vindo ao Viva Mobil! Seu cadastro foi enviado para análise.',
    'register_update_success' => 'Seus dados foram atualizados! Aguarde a nova análise.',
    'register_already_exists' => 'Já existe um cadastro com este CPF. Use a opção de login ou recupere sua senha.',

    // === Validação de dados ===
    'validation_success' => 'Dados validados! Você pode prosseguir para a próxima etapa.',
    'cpf_invalid' => 'O CPF informado não é válido. Verifique e tente novamente.',

    // === E-mail ===
    'email_code_sent' => 'Enviamos um código de verificação para :email. Confira sua caixa de entrada.',
    'email_code_resent' => 'Um novo código foi enviado para :email.',
    'email_code_verified' => 'E-mail verificado com sucesso!',
    'email_code_invalid' => 'Código incorreto. Verifique o último código recebido no seu e-mail.',
    'email_code_already_used' => 'Este código já foi utilizado. Solicite um novo.',

    // === Senha ===
    'password_changed' => 'Senha alterada com sucesso!',
    'password_wrong' => 'A senha atual está incorreta.',
    'password_reset_sent' => 'Enviamos um código de recuperação para :email.',
    'password_reset_resent' => 'Um novo código de recuperação foi enviado para :email.',
    'password_reset_code_verified' => 'Código verificado! Agora defina sua nova senha.',

    // === Sessão ===
    'logout_success' => 'Você foi desconectado com segurança.',
    'token_refreshed' => 'Sessão renovada.',
    'token_revoked' => 'Sessão encerrada.',
    'session_expired' => 'Sua sessão expirou. Faça login novamente para continuar.',

    // === Permissões ===
    'unauthorized' => 'Você precisa estar conectado para acessar este recurso.',
    'forbidden' => 'Você não tem permissão para realizar esta ação.',
];
```

### Regras para mensagens humanizadas

**Tom de voz:**
- Fale COM o usuário, não SOBRE o sistema
- Use "você" e "sua/seu" (pessoal, direto)
- Seja empático em erros — reconheça o problema e oriente
- Comemore conquistas — registro, viagem confirmada, etc.
- Evite jargão técnico — o usuário é um cidadão PNE, não um dev

**Estrutura de mensagens:**

| Tipo | Padrão | Exemplo |
|---|---|---|
| **Sucesso de ação** | O que aconteceu + próximo passo (se houver) | "Seu cadastro foi enviado para análise. Você receberá uma notificação em breve." |
| **Sucesso de consulta** | Silêncio (dados no `data` bastam) ou contexto | Não precisa de mensagem, ou "3 viagens encontradas" |
| **Erro de validação** | O que está errado + como corrigir | "O CPF informado não é válido. Verifique os números e tente novamente." |
| **Erro de estado** | O que impediu + alternativa | "Não é possível cancelar esta viagem pois falta menos de :hours horas. Para urgências, entre em contato com a central." |
| **Erro de permissão** | Claro e direto | "Você não tem permissão para realizar esta ação." |
| **Erro de servidor** | Desculpe + orientação | "Desculpe, não foi possível processar sua solicitação. Tente novamente em alguns minutos." |

**EVITE:**
```php
// ❌ Genérico
'Recurso criado com sucesso!'
'Operação realizada!'
'Requisição processada com sucesso.'
'Erro ao processar a requisição.'

// ❌ Técnico
'Conflito de estado do recurso.'
'Token invalidado com sucesso!'
'422 Unprocessable Entity'

// ❌ Robótico
'Endereço ID 45 salvo com sucesso!'
'A entidade TripBooking foi atualizada.'
```

**USE:**
```php
// ✅ Humanizado
'Endereço salvo! Você pode usá-lo nas próximas solicitações.'
'Sua viagem para :destination foi registrada! Aguarde a confirmação.'
'Desculpe, algo deu errado. Tente novamente em alguns instantes.'
```

### Passo 5: Refatorar o trait Response

Atualize os defaults do trait `Response` para usar traduções:

```php
// Dentro do trait Response, trocar defaults hardcoded:

// ANTES
public static function successResponse($data = [], $message = 'Requisição processada com sucesso.', $code = 200)

// DEPOIS
public static function successResponse($data = [], ?string $message = null, int $code = 200)
{
    return response()->json([
        'success' => true,
        'message' => $message ?? __('common.request_success'),
        'data' => $data,
    ], $code);
}

// ANTES
public static function internalServerErrorResponse($exception)
{
    // ...
    'message' => 'Não foi possível processar a requisição. Tente novamente mais tarde.',

// DEPOIS
    'message' => __('common.server_error'),
```

### Passo 6: Refatorar Controllers

Trocar todas as strings hardcoded por chamadas `__()`:

```php
// ANTES
return self::successResponse($data, 'Conta criada com sucesso!', 201);

// DEPOIS
return self::successResponse($data, __('auth.register_success'), 201);

// ANTES (com variáveis)
return self::successResponse($data, 'Código de recuperação de senha enviado com sucesso! Enviado para o e-mail: ' . $result['email']);

// DEPOIS
return self::successResponse($data, __('auth.password_reset_sent', ['email' => $result['email']]));
```

### Passo 7: Refatorar Services (exceções)

```php
// ANTES
throw new InvalidArgumentException('Este usuário já possui cadastro, por favor realize o login!');

// DEPOIS
throw new InvalidArgumentException(__('auth.register_already_exists'));

// ANTES (com variáveis)
throw new UserAlreadyHasTripException("Você já possui uma solicitação de viagem para {$date}.");

// DEPOIS
throw new UserAlreadyHasTripException(__('trip.already_exists', ['date' => $date]));
```

### Passo 8: Refatorar e-mails

```blade
{{-- ANTES --}}
<h1>Olá!</h1>
<p>Olá {{ $name }}, você está recebendo este e-mail porque recebemos uma solicitação de recuperação de senha.</p>

{{-- DEPOIS --}}
<h1>@lang('notification.email_greeting', ['name' => $name])</h1>
<p>@lang('notification.forgot_password_body')</p>
```

### Passo 9: Criar traduções em es e en

Para cada arquivo em `lang/pt_BR/`, crie a versão equivalente em `lang/es/` e `lang/en/`.

**Regras para tradução:**
- Manter o mesmo tom humanizado em todos os idiomas
- Adaptar expressões culturais (não traduzir literalmente)
- Manter os `:placeholders` nos mesmos lugares
- Espanhol: usar "usted" (formal, serviço público)
- Inglês: usar tom amigável e direto

**Exemplo:**
```php
// lang/es/auth.php
'register_success' => '¡Bienvenido a Viva Mobil! Su registro fue enviado para análisis.',
'unauthorized' => 'Necesita iniciar sesión para acceder a este recurso.',

// lang/en/auth.php
'register_success' => 'Welcome to Viva Mobil! Your registration has been submitted for review.',
'unauthorized' => 'Please sign in to access this resource.',
```

### Passo 10: Verificação

1. Teste com `Accept-Language: en`:
   ```bash
   curl -H "Accept-Language: en" http://localhost/api/v1/auth/login -X POST
   # Response deve vir em inglês
   ```

2. Teste com `?lang=es`:
   ```bash
   curl http://localhost/api/v1/trips/future?lang=es
   # Response deve vir em espanhol
   ```

3. Teste sem header (deve usar default pt_BR):
   ```bash
   curl http://localhost/api/v1/trips/future
   # Response deve vir em português
   ```

4. Verifique o header `Content-Language` na response

5. Rode `php artisan lang:show` se disponível para verificar chaves faltantes

## Escopo: refactor

Se a infraestrutura já existe e o objetivo é apenas converter strings hardcoded:

1. Use `Grep` para encontrar TODAS as strings hardcoded nos controllers e services
2. Para cada string, identifique o módulo (auth, trip, route, etc.)
3. Adicione a chave no arquivo de tradução correto
4. Substitua a string pela chamada `__('modulo.chave')`
5. Replique a chave nos outros idiomas
6. Teste

## Escopo: add-locale

Para adicionar um novo idioma a um projeto que já tem i18n:

1. Copie a estrutura de `lang/pt_BR/` para `lang/{novo_locale}/`
2. Traduza todos os arquivos
3. Adicione o locale em `config('vivamobil.locales.supported')`
4. Adicione aliases em `config('vivamobil.locales.aliases')` se necessário
5. Teste

## Regras importantes

- NUNCA use strings hardcoded para mensagens voltadas ao usuário
- SEMPRE use `__('modulo.chave')` ou `trans('modulo.chave')`
- SEMPRE inclua variáveis via `:placeholder` (não concatenar strings)
- SEMPRE organize por domínio de negócio (não um `messages.php` gigante)
- SEMPRE mantenha paridade entre idiomas (mesmas chaves em todos)
- SEMPRE humanize — o usuário é uma pessoa, não uma máquina
- O middleware SetLocale deve ser o PRIMEIRO middleware da stack API
- Use `Content-Language` header na response para informar o idioma usado
- Validações (FormRequest) já usam o sistema de lang do Laravel — apenas garanta que `lang/{locale}/validation.php` existe
- Se o projeto tem config customizada, centralize locales nela (não em app.php)
