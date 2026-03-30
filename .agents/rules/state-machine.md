Transições de estado SEMPRE no Service, NUNCA no Controller ou Model.

- Validar transição com `canTransitionTo()` do Enum antes de executar
- SEMPRE dentro de `DB::transaction()`
- Transições em cascata (ex: tickets ida+volta vinculados) na mesma transaction
- Validações temporais (ex: cancelamento 12h antes) via config, não hardcoded
- Log toda transição com: entity_id, from_status, to_status, user_id
- Notificações APÓS a transaction (usar `DB::afterCommit()` ou fora do closure)

Consultar `docs/02-Viva-Mobil-Documentacao-de-Negocio.md` para regras de negócio antes de implementar transições.

## Enum de Estado — Padrão Obrigatório

TODO enum de estado (status) DEVE implementar `allowedTransitions()` e `canTransitionTo()`:

```php
enum ExemploStatusEnum: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case FINISHED = 'finished';

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::ACTIVE],
            self::ACTIVE => [self::FINISHED],
            self::FINISHED => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions());
    }
}
```

- Modelo de referência: `App\Packages\Registration\Enum\RegistrationStatusEnum`
- NUNCA validar transições com `in_array($slug, ['status1', 'status2'])` hardcoded
- SEMPRE usar `$currentEnum->canTransitionTo($targetEnum)` no Service

## Exceções em Transições de Estado

Usar a exceção correta conforme o tipo de erro:

| Situação | Exceção | HTTP Status |
|---|---|---|
| Transição inválida (estado atual não permite ir para o alvo) | `ConflictHttpException` | 409 |
| Entidade já está no estado final (ex: já cancelada, já finalizada) | `ConflictHttpException` | 409 |
| Argumento inválido (dados malformados, não relacionado a estado) | `InvalidArgumentException` | 400 |
| Entidade não encontrada | `ModelNotFoundException` | 404 |

```php
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

if (! $currentEnum->canTransitionTo($targetEnum)) {
    throw new ConflictHttpException(__('modulo.invalid_transition'));
}
```

- NUNCA usar `InvalidArgumentException` para conflitos de estado — usar `ConflictHttpException`
- `returnError()` do trait `Response` já trata `ConflictHttpException` retornando 409
