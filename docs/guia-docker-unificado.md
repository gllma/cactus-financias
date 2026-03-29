# Guia Unificado Docker — Cactus Financias (MVP)

## Objetivo
Centralizar instalação, build, deploy e operação do projeto em **um único fluxo** usando apenas Docker + Make.

## Pré-requisitos
- Git
- Docker + Docker Compose
- Make

## Fluxo oficial
### 1) Clonar
```bash
git clone <repo-url>
cd cactus-financias
```

### 2) Instalar tudo (primeira vez)
```bash
make install
```
> O alvo `install` cria `.env`, builda imagens, sobe containers e valida health.

### 3) Deploy após alterações de código
```bash
make deploy
```
> O alvo `deploy` sempre atualiza containers com `--build --force-recreate --remove-orphans`.

## Operação diária
```bash
make ps
make logs
make health
make down
```

## Entrar nos containers
```bash
make in SERVICE=backend
make in SERVICE=frontend
make in SERVICE=db
```

## Endpoints de avaliação
- Frontend: http://localhost:3000
- Perfil: http://localhost:3000/profile-preferences
- Observabilidade: http://localhost:3000/observability-dashboard
- Backend health: http://localhost:8000/health

## Troubleshooting
### Docker não encontrado
Instale Docker Desktop/Engine e garanta `docker compose` disponível.

### Make não encontrado
Instale `make` e rode novamente.

### Falha no `make deploy`
Execute `make logs` para identificar o serviço com erro e depois rode `make deploy` novamente.

### Falha na sincronização da branch
`make sync` depende de branch `main` local/remota configurada.
