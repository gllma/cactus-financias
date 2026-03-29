# Runbook — Subir o Cactus Financias (MVP)

> Para guia completo de instalação + subida, veja também: `docs/instalacao-e-execucao.md`.

## Pré-requisitos
- Docker + Docker Compose
- Git
- Make

## 1) Clonar e entrar no projeto
```bash
git clone <repo-url>
cd cactus-financias
```

## 2) Buildar e subir com Makefile
```bash
make build
make up
```

Serviços expostos:
- Backend: http://localhost:8000
- Frontend: http://localhost:3000
- Postgres: localhost:5432

## 3) Fluxo para avaliação em navegador
- Abra `http://localhost:3000/profile-preferences` para validar avatar por iniciais e troca de tema persistida em backend SQLite.
- Abra `http://localhost:3000/observability-dashboard` para validar carregamento do resumo de observabilidade.
- No topo da aplicação, altere Nome/E-mail e clique em **Salvar sessão** para simular usuários diferentes.
- Para validar bloqueio de observabilidade (403), use um e-mail fora da `OBSERVABILITY_ALLOWLIST`.

## 4) Validar saúde básica
```bash
make health
```

## 5) Encerrar ambiente
```bash
make down
```

## 6) Entrar nos containers
```bash
make in SERVICE=backend
make in SERVICE=frontend
make in SERVICE=db
```

## Observações importantes
- Este runbook segue estratégia Docker-first (sem instalação local de dependências da aplicação).
- Toda a orquestração de build/up/down/deploy está centralizada no `Makefile`.
- O backend utiliza bootstrap em PHP com persistência SQLite para suportar os fluxos do MVP nesta etapa.
- A integração Laravel completa permanece no roadmap técnico.
