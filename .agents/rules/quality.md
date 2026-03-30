## Qualidade — Checks Obrigatórios

### Antes de commit
1. Rodar `make lint` (Laravel Pint PSR-12)
2. Rodar agent `reviewer` para validar a implementação
3. Verificar que `make pest` passa (quando containers estiverem ativos)

### Checks do reviewer que devem ser automáticos
- [ ] Toda query SQL está em Repository (nunca em Service/Controller)
- [ ] Dados recebidos via DTO são persistidos (reason, fields, notes)
- [ ] Arquivos de config referenciados existem (`config/vivamobil.php`)
- [ ] Enums de estado têm `allowedTransitions()` e `canTransitionTo()`
- [ ] Mensagens i18n usam `:placeholder`, nunca concatenação
- [ ] `firstOrCreate()` usa campo único como chave (slug, não múltiplos campos)
- [ ] State transitions geram audit log (entity_id, from, to, user_id)
- [ ] Validações de negócio estão no Service, não no Controller
- [ ] Cancelamentos/rejeições em cascata estão na mesma transaction
- [ ] Colunas referenciadas em queries SQL existem no schema real (ver `database.md`)
- [ ] FormRequests têm `use Response` e `failedValidation()` override
- [ ] Validation rules `exists:` e `unique:` NÃO usam schema prefix (ver `database.md`)
- [ ] Conflitos de estado usam `ConflictHttpException` (409), não `InvalidArgumentException` (400)
- [ ] Exceções no `returnError()` retornam resultado (verificar que todo `self::method()` tem `return`)
