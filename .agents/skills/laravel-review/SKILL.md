---
name: laravel-review
description: "Code review de código Laravel verificando padrões arquiteturais, separation of concerns, naming conventions, segurança e boas práticas do projeto"
user-invocable: true
allowed-tools: Read, Grep, Glob, Bash, Agent
argument-hint: "[arquivo-ou-diretorio] [--scope=full|recent|staged]"
---

# Laravel Code Review

Realiza code review verificando aderência aos padrões arquiteturais do projeto.

## Passo 1: Determinar escopo do review

Baseado nos argumentos:
- Se `$ARGUMENTS` for um arquivo/diretório → revisar apenas esse escopo
- Se `--scope=staged` → revisar arquivos no git staging (`git diff --cached --name-only`)
- Se `--scope=recent` → revisar último commit (`git diff HEAD~1 --name-only`)
- Se `--scope=full` → revisar toda a codebase (focar nos pontos mais críticos)
- Se nenhum argumento → revisar arquivos staged, ou se não houver, o último commit

## Passo 2: Entender os padrões do projeto

1. Leia o `CLAUDE.md` do projeto
2. Identifique o padrão arquitetural (Modular/Packages, DDD, Laravel padrão)
3. Identifique as convenções de naming analisando 2-3 arquivos existentes de cada camada

## Passo 3: Revisar cada arquivo

Para cada arquivo no escopo, aplique os checks relevantes ao tipo do arquivo:

---

### Controllers — Checks

| ID | Check | Severidade |
|---|---|---|
| C01 | **Sem lógica de negócio** — Controller deve apenas: receber request, chamar service, retornar response | ERRO |
| C02 | **Sem queries** — Nenhum uso de `::where()`, `::find()`, `DB::`, `->query()` no controller | ERRO |
| C03 | **Usa FormRequest** — Métodos store/update devem receber FormRequest tipado, não `Request` genérico | ERRO |
| C04 | **Retorna Resource** — Nunca retorna Model direto; usa JsonResource ou Response trait | ERRO |
| C05 | **Naming correto** — `{Entity}Controller`, métodos seguem convenção REST (index, store, show, update, destroy) | AVISO |
| C06 | **Sem try/catch excessivo** — Se o projeto usa trait Response com exception handling, não duplicar | AVISO |
| C07 | **Injeção de dependência** — Services injetados via constructor ou method injection, nunca instanciados com `new` | ERRO |

---

### Services — Checks

| ID | Check | Severidade |
|---|---|---|
| S01 | **Single responsibility** — Cada service faz UMA ação (Create, Update, Delete, List, etc.) | ERRO |
| S02 | **Sem queries diretas** — Usa Repository para acesso a dados, não Eloquent/DB direto | ERRO |
| S03 | **Transaction em escrita** — Operações de create/update/delete usam `DB::transaction()` ou similar | AVISO |
| S04 | **Naming correto** — `{Action}{Entity}Service` (ex: CreateContractService) | AVISO |
| S05 | **Recebe DTO** — Operações de escrita recebem DTO tipado, não array genérico | AVISO |
| S06 | **Sem referência a Request/Response** — Service não deve conhecer HTTP (sem Request, sem response helpers) | ERRO |
| S07 | **Logging adequado** — Operações críticas logam no channel correto do projeto | INFO |
| S08 | **Não é God Service** — Se tiver mais de 5 métodos públicos, provavelmente deve ser dividido | AVISO |

---

### Repositories — Checks

| ID | Check | Severidade |
|---|---|---|
| R01 | **Extende base** — Extende BaseRepository/BaseEloquentRepository do projeto | AVISO |
| R02 | **Interface existe** — Se o projeto usa padrão Interface+Implementation, ambos devem existir | ERRO |
| R03 | **Binding registrado** — Interface registrada no ServiceProvider de repositories | ERRO |
| R04 | **Sem lógica de negócio** — Repository faz query e retorna dados, não processa regras | ERRO |
| R05 | **Sem N+1** — Queries que retornam coleções usam `with()` para eager loading | AVISO |

---

### Models — Checks

| ID | Check | Severidade |
|---|---|---|
| M01 | **$fillable definido** — Nunca usar `$guarded = []` | ERRO |
| M02 | **$casts definido** — Campos date, enum, json, boolean devem ter cast explícito | AVISO |
| M03 | **Relationships tipadas** — Relationships devem ter return type (HasMany, BelongsTo, etc.) | INFO |
| M04 | **Sem lógica de negócio** — Model define estrutura e relationships, não regras de negócio | AVISO |
| M05 | **Factory existe** — Model deve ter factory correspondente para testes | INFO |
| M06 | **Soft deletes** — Se o projeto usar soft deletes como padrão, model deve ter o trait | INFO |

---

### FormRequests — Checks

| ID | Check | Severidade |
|---|---|---|
| F01 | **Rules completas** — Todos os campos do DTO/Model devem ter validação | AVISO |
| F02 | **Tipos corretos** — Validações correspondem ao tipo do campo (string, integer, email, etc.) | ERRO |
| F03 | **Required vs nullable** — Campos obrigatórios marcados como `required`, opcionais como `nullable` | AVISO |
| F04 | **Exists rules** — Foreign keys validadas com `exists:table,column` | AVISO |
| F05 | **Messages em pt-BR** — Se o projeto usar localização, mensagens customizadas em português | INFO |

---

### Migrations — Checks

| ID | Check | Severidade |
|---|---|---|
| G01 | **Índices em FKs** — Foreign keys devem ter índice | AVISO |
| G02 | **Timestamps presentes** — `$table->timestamps()` presente | AVISO |
| G03 | **Schema correto** — Usa o schema adequado do projeto (admin.*, social.*, public.*) | AVISO |
| G04 | **Down reversível** — Método `down()` desfaz o `up()` corretamente | INFO |

---

### DTOs — Checks

| ID | Check | Severidade |
|---|---|---|
| D01 | **Consistente com projeto** — Se o projeto usa `spatie/laravel-data`, DTOs devem extender `Data`. Se usa readonly classes, seguir esse padrão. Não misturar | AVISO |
| D02 | **Usado no Service** — Services de escrita devem receber DTO tipado, não `array` ou `Request` | AVISO |
| D03 | **Campos tipados** — Todos os campos do DTO devem ter type hint explícito | INFO |

---

### Rotas — Checks

| ID | Check | Severidade |
|---|---|---|
| RT01 | **Middleware de auth** — Endpoints protegidos têm middleware de autenticação | ERRO |
| RT02 | **Versionamento** — Rotas sob prefixo de versão (v1) | AVISO |
| RT03 | **Naming convention** — Rotas nomeadas seguindo padrão do projeto | INFO |
| RT04 | **Throttle** — Rate limiting aplicado | INFO |

---

### Testes — Checks

| ID | Check | Severidade |
|---|---|---|
| T01 | **Teste existe** — Feature ou endpoint novo deve ter teste correspondente | ERRO |
| T02 | **Usa factory** — Dados de teste criados via factory, não insert manual | AVISO |
| T03 | **Testa validação** — Testa cenário 422 (dados inválidos) | AVISO |
| T04 | **Testa auth** — Testa cenário 401 (sem autenticação) | AVISO |
| T05 | **Assertions completas** — Verifica status, estrutura JSON, e dados no banco | AVISO |

---

### Documentação API (Scramble) — Checks

| ID | Check | Severidade |
|---|---|---|
| DOC01 | **Controller tem Group** — `#[Group('Nome')]` presente para organizar endpoints | AVISO |
| DOC02 | **PHPDoc em métodos** — Cada método público do controller tem descrição clara do que faz, para quem, quando usar | ERRO |
| DOC03 | **FormRequest documentado** — Campos em `rules()` têm comentário descritivo acima | AVISO |
| DOC04 | **Resource anotada** — `@mixin Model` ou `@property Model $resource` presente | AVISO |
| DOC05 | **Throws documentado** — Exceções relevantes anotadas com `@throws` | INFO |
| DOC06 | **Enums documentados** — Cada caso do enum tem PHPDoc | INFO |
| DOC07 | **Query params de listagem** — Filtros e paginação documentados via atributos ou PHPDoc | AVISO |

---

### i18n / Mensagens — Checks

| ID | Check | Severidade |
|---|---|---|
| I18N01 | **Sem strings hardcoded** — Mensagens voltadas ao usuário (responses, exceções, emails) usam `__()` ou `trans()`, nunca string literal | ERRO |
| I18N02 | **Chave existe nos 3 idiomas** — Toda chave usada em `__()` deve existir em pt_BR, es e en | ERRO |
| I18N03 | **Variáveis via placeholder** — Dados dinâmicos via `:placeholder`, nunca concatenação de strings | AVISO |
| I18N04 | **Tom humanizado** — Mensagens escritas para o usuário final (cidadão PNE), não para devs. Sem jargão técnico | AVISO |
| I18N05 | **Arquivo correto** — Chave no arquivo do domínio certo (auth.*, trip.*, route.*, não tudo em common.*) | INFO |

---

### State Machine / Transições de Estado — Checks

| ID | Check | Severidade |
|---|---|---|
| SM01 | **Transição no Service** — Mudanças de status devem ocorrer exclusivamente em Services dedicados, nunca em Controllers, Models ou diretamente no banco | ERRO |
| SM02 | **Validação de transição** — Service deve verificar se a transição de estado é permitida antes de executar (ex: `canTransitionTo()`) | ERRO |
| SM03 | **Transaction em transição** — Transições de estado devem estar dentro de `DB::transaction()` | ERRO |
| SM04 | **Cascata tratada** — Se entidades vinculadas devem mudar junto (ex: tickets ida+volta), ambas são tratadas na mesma transaction | ERRO |
| SM05 | **Validação temporal** — Se há prazo para ação (ex: cancelamento até 12h antes), validar no Service | AVISO |
| SM06 | **Enum com allowedTransitions** — Se o projeto usa Enums de status, eles devem definir `allowedTransitions()` ou equivalente | AVISO |
| SM07 | **Log de transição** — Transições de estado devem ser logadas com from/to/user/timestamp | AVISO |

---

### PostGIS / Geoespacial — Checks (se o projeto usa PostGIS)

| ID | Check | Severidade |
|---|---|---|
| GEO01 | **Tipo correto** — Campos geoespaciais usam tipos PostGIS (`point`, `geometry`, `geography`), não `string` ou `json` | ERRO |
| GEO02 | **Índice espacial** — Campos geoespaciais consultados frequentemente têm `spatialIndex` | AVISO |
| GEO03 | **Query segura** — Queries geoespaciais usam funções PostGIS (`ST_Distance`, `ST_Within`), não cálculos manuais de lat/lng | AVISO |

---

### Configurabilidade — Checks (todos os arquivos)

| ID | Check | Severidade |
|---|---|---|
| CFG01 | **Sem hardcoding de valores** — Nenhum valor de negócio hardcoded no código (limites, prazos, thresholds, textos, URLs). Deve estar em `.env`, `config/`, ou tabela `settings` | ERRO |
| CFG02 | **Config via .env ou settings** — Valores configuráveis acessados via `config('key')` ou `Setting::get('key')`, nunca por constantes ou literais no Service/Controller | ERRO |
| CFG03 | **Textos externalizados** — Mensagens de notificação, e-mail e respostas da API devem usar lang files ou config, não strings literais no código | AVISO |
| CFG04 | **Timeouts e limites** — Valores como "12h de antecedência", "15 min de tolerância", "paginação de 15 itens" devem ser configuráveis, não hardcoded | AVISO |

Exemplos de hardcoding a evitar:
```php
// ❌ ERRADO — hardcoded
if ($route->departure_time->diffInHours(now()) < 12) { ... }
$query->paginate(15);
$tolerance = 10; // minutos

// ✅ CORRETO — configurável
if ($route->departure_time->diffInHours(now()) < config('vivamobil.cancellation_deadline_hours')) { ... }
$query->paginate(config('vivamobil.pagination.per_page', 15));
$tolerance = config('vivamobil.boarding.tolerance_minutes', 10);
```

---

### Segurança — Checks (todos os arquivos)

| ID | Check | Severidade |
|---|---|---|
| SEC01 | **Sem SQL injection** — Não usa `DB::raw()` com input do usuário sem binding | ERRO |
| SEC02 | **Sem mass assignment** — Não usa `Model::create($request->all())` | ERRO |
| SEC03 | **Sem secrets hardcoded** — Não tem senhas, tokens, chaves no código | ERRO |
| SEC04 | **Sem dd()/dump()** — Não tem debug helpers esquecidos | AVISO |
| SEC05 | **Sem .env values** — Não acessa `$_ENV` direto; usa `config()` ou `env()` via config | AVISO |

---

## Passo 4: Gerar relatório

Apresente o resultado no seguinte formato:

```
## Code Review — {escopo}

### Resumo
- X arquivos revisados
- X erros encontrados
- X avisos encontrados
- X sugestões

### Erros (devem ser corrigidos)

**[C01] app/Http/Controllers/ContractController.php:45**
Lógica de negócio no controller — cálculo de saldo deveria estar no Service.
```php
// Atual (errado)
$balance = $contract->value - $contract->payments->sum('amount');

// Sugerido
$balance = $this->calculateBalanceService->execute($contract);
```

### Avisos (recomendado corrigir)
...

### Sugestões (melhorias opcionais)
...
```

## Passo 5: Oferecer correção

Após apresentar o relatório, pergunte ao usuário se deseja que os erros e avisos sejam corrigidos automaticamente.

## Regras do review

- Seja pragmático — não reporte style issues que o Pint/formatter resolve
- Foque em problemas arquiteturais e de segurança, não em preferências cosméticas
- Se um padrão diferente é usado consistentemente no projeto, não force outro
- ERROs são violações objetivas dos padrões do projeto
- AVISOs são desvios de boas práticas que podem ser justificáveis
- INFOs são sugestões de melhoria que podem ser ignoradas
- Sempre mostre o código atual e a sugestão de correção
