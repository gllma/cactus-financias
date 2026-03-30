Todos os comandos rodam via Docker através do Makefile. NUNCA executar `php`, `composer`, `artisan`, `pest` ou `npm` diretamente no host — PHP não está instalado fora do container.

SEMPRE usar targets do Makefile. Referência completa:

```bash
# Docker
make build                           # Build containers
make up                              # Start containers
make down                            # Stop containers
make restart                         # Restart completo
make shell-php                       # Shell no container PHP
make logs / make logs-php            # Logs dos containers

# Composer
make composer-install                # Instalar dependências
make composer-update                 # Atualizar dependências
make composer-require lib=nome/pkg   # Adicionar pacote
make composer-dump                   # Dump autoload

# Database
make migrate                         # Rodar migrations
make migrate-fresh                   # Fresh migration
make db-seed                         # Seed do banco
make recreate-database               # Recriar banco
make recreate-testing-database       # Recriar banco de testes
make restore filename=backup.sql     # Restaurar backup

# Testes
make pest                            # Rodar todos os testes (Pest)
make pest-group GROUP=auth           # Rodar grupo específico
make pest-test TEST=tests/Feature/X  # Rodar teste específico
make pest-report                     # Testes com relatório JUnit

# Qualidade
make lint                            # Laravel Pint (PSR-12)
make openapi                         # Exportar OpenAPI (Scramble)

# Cache
make clear                           # Limpar caches
make optimize                        # Cachear config, routes, views
make cache                           # Alias de optimize

# Deploy
make deploy                          # Deploy com migrations
make install                         # Setup inicial completo
```

Se um target necessário NÃO existir no Makefile, crie-o seguindo o padrão dos existentes (usando `$(DC) exec $(DOCKER_SERVICE_PHP_FPM)`). NUNCA contorne o Makefile com docker compose direto.

## Dependências

Antes de usar qualquer pacote no código, verificar se está instalado:

```bash
# Verificar se pacote existe
make artisan cmd="package:discover" 2>/dev/null
# Ou checar composer.json diretamente
```

Se o pacote NÃO estiver instalado, instalar via Makefile ANTES de usá-lo no código:

```bash
make composer-require lib=dedoc/scramble
make composer-require lib=pgvector/pgvector
make composer-require lib=kreait/laravel-firebase
```

NUNCA assumir que um pacote está instalado. NUNCA rodar `composer require` direto — sempre `make composer-require lib=pacote`.
