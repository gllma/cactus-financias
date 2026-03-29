# Guia Unificado Docker — Cactus Financias (MVP)

## Objetivo
Centralizar instalação, build, deploy e operação do projeto em **um único fluxo** usando apenas Docker + Make.

## Pré-requisitos
- Git
- Docker + Docker Compose
- Make

## Passo a passo (instalação + uso)
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

### 3) Abrir o sistema
- Frontend: http://localhost:3000
- Perfil: http://localhost:3000/profile-preferences
- Observabilidade: http://localhost:3000/observability-dashboard
- Backend health: http://localhost:8000/health

### 4) Uso básico
1. No topo da aplicação, preencha Nome/E-mail e clique em **Salvar sessão**.
2. Vá em **Perfil** e altere tema claro/escuro.
3. Vá em **Observabilidade** e atualize a janela em minutos.
4. Para validar bloqueio 403, use e-mail fora de `OBSERVABILITY_ALLOWLIST`.

### 5) Após alterar código
```bash
make deploy
```
> O `deploy` sempre atualiza containers com `--build --force-recreate --remove-orphans`.

## Entrar nos containers
```bash
make in SERVICE=backend
make in SERVICE=frontend
make in SERVICE=db
```

## Diagnóstico quando frontend não sobe
### Passo 1: validar status
```bash
make ps
```

### Passo 2: ver logs
```bash
make doctor
```

### Passo 3: rebuild completo
```bash
make rebuild
```

### Passo 4: validar health backend
```bash
make health
```

## Comandos úteis
```bash
make help
make logs
make down
make clean
```

## Troubleshooting
### Docker não encontrado
Instale Docker Desktop/Engine e garanta `docker compose` disponível.

### Make não encontrado
Instale `make` e rode novamente.

### Falha na sincronização da branch
`make sync` depende de branch `main` local/remota configurada.
