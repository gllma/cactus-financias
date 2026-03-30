Este projeto usa autenticação customizada com stored procedures PostgreSQL. NÃO usa Sanctum nem Passport.

- Login via stored procedure: `admin.process_login(username, password, environment)`
- Tokens em `public.personal_access_tokens` + cache Redis
- Middleware: `App\Packages\Auth\Middlewares\AuthenticateMiddleware`
- Permissões via middleware: `middleware('auth:permission.slug')`
- Endpoints admin: `middleware('auth:admin.entity.action')`
- Nos testes: NUNCA usar `actingAs()`. Usar o trait `Tests\Traits\AuthenticatesForTesting`
- Helper `userObject()` retorna usuário autenticado do cache

## Testes de Autenticação

Trait: `Tests\Traits\AuthenticatesForTesting`

Configuração em `tests/Pest.php`:
```php
pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\DatabaseTransactions::class)
    ->use(Tests\Traits\AuthenticatesForTesting::class)
    ->in('Feature');
```

**IMPORTANTE:** `CACHE_STORE` DEVE ser `array` nos testes (configurado em `phpunit.xml` e `.env.testing`). Com `file`, cache de tokens/permissões persiste entre testes e causa 403 espúrios quando IDs de usuários são reutilizados após rollback.

Uso nos testes:
```php
beforeEach(function () {
    $manager = $this->createManagerWithPermissions(['admin.registration.list']);
    $this->token = $manager['token'];
    $this->managerId = $manager['user']->id;
});

it('lista cadastros', function () {
    $response = $this->authGet($this->token, '/v1/admin/registrations');
    $response->assertOk();
});
```

Para endpoints que usam `userObject()` dentro de Services, popular o cache no `beforeEach`:
```php
Cache::put('user_id_' . $this->managerId, (object) [
    'user' => (object) [
        'id' => $this->managerId,
        'username' => 'manager',
        'active' => true,
        'person_id' => null,
        'permissions' => ['admin.registration.approve'],
    ],
], 3600);
```

Helpers disponíveis:
- `createManagerWithPermissions(array $permissions)` — cria gestor com permissões específicas
- `createManagerWithAllPermissions()` — todas as permissões EP-03
- `createUserWithoutPermissions()` — para testar 403
- `authGet($token, $uri)`, `authPost(...)`, `authPut(...)`, `authDelete(...)` — requests autenticados
