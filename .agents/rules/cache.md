Cache via Redis usando `CacheTrait` (`App\Base\Traits\CacheTrait`). Respeitar os padrões existentes.

## Dados de lookup (tabelas de referência)

Dados estáticos ou pouco mutáveis devem ser cacheados usando o padrão de helper em `app/Base/Helpers/data.php`.

Chaves de cache devem ser constantes no Model para evitar typos e facilitar busca:

```php
// No Model
class NovaEntidade extends Model {
    public const CACHE_KEY = 'novaEntidade';
}

// No helper (app/Base/Helpers/data.php)
function novaEntidadeInCache(): mixed {
    return (new class {use CacheTrait;})->cache(
        key: NovaEntidade::CACHE_KEY,
        callback: function () {
            return NovaEntidade::orderBy('name')->get();
        },
        seconds: config('api.cache.ttl'),
    );
}
```

Ao criar um novo lookup cacheável:
1. Adicionar `public const CACHE_KEY = 'nomeEmCamelCase'` no Model
2. Adicionar a function no `app/Base/Helpers/data.php` usando `Model::CACHE_KEY`
3. Seguir o padrão `{entidadeNoCamelCase}InCache()`
4. TTL SEMPRE via `config('api.cache.ttl')`, nunca hardcoded
5. Usar nos controllers/services: `$items = novaEntidadeInCache()`
6. Invalidar com `Cache::forget(NovaEntidade::CACHE_KEY)`

## Tokens de autenticação

Tokens cacheados em Redis via services dedicados em `app/Packages/Auth/Services/Cache/`:
- `TokenInCacheService` → chave `token_{hash}`
- `RefreshTokenInCacheService` → chave `refresh_token_{hash}`
- `UserInCacheByTokenService` → chave `user_id_{id}`

TTL de tokens via `SettingsEnum::TOKEN_EXPIRATION_MINUTES` (em minutos, multiplicar por 60 para segundos).

NUNCA manipular cache de tokens diretamente — usar os services existentes.

## Convenção de chaves

| Padrão | Exemplo | Uso |
|---|---|---|
| `{entidade}` | `settings`, `roles`, `busSeatTypes` | Dados de lookup |
| `token_{hash}` | `token_abc123...` | Access token |
| `refresh_token_{hash}` | `refresh_token_xyz...` | Refresh token |
| `user_id_{id}` | `user_id_42` | Dados do usuário autenticado |

Chaves em **camelCase** para lookup, **snake_case com prefixo** para tokens.

## Invalidação de cache

- **Ao alterar dado de lookup:** `Cache::forget(Model::CACHE_KEY)`
- **Ao alterar token/sessão:** usar métodos do `CacheTrait`: `clearAccessToken()`, `clearRefreshToken()`, `clearUserCache()`
- **Ao alterar dados do usuário:** invalidar `user_id_{id}` para forçar reload

## CacheTrait

O trait `App\Base\Traits\CacheTrait` já trata:
- Cache desabilitado via `config('api.cache.use_cache')` → executa callback direto
- Falha de conexão Redis (`Predis\Connection\ConnectionException`) → fallback para callback sem cache
- TTL default via `config('api.cache.ttl')` (86400s = 1 dia)

## Regras

- SEMPRE usar `CacheTrait` ou os helpers `*InCache()` — nunca `Cache::remember()` direto
- SEMPRE chaves de cache como constante `CACHE_KEY` no Model — nunca string literal solta
- SEMPRE TTL via config, nunca hardcoded
- SEMPRE invalidar cache ao alterar dados cacheados (CRUD em tabelas de lookup)
- Para novos services de cache de auth, seguir o padrão de `app/Packages/Auth/Services/Cache/`
- Em testes, `CACHE_STORE=array` (configurado em `.env.testing`) — cache funciona mas não persiste entre requests
