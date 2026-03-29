# Runbook — Subir o Cactus Financias (MVP)

## Pré-requisitos
- Docker + Docker Compose
- Git

## 1) Clonar e entrar no projeto
```bash
git clone <repo-url>
cd cactus-financias
```

## 2) Configurar variáveis de ambiente
```bash
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env
```

> Ajuste a allowlist em `OBSERVABILITY_ALLOWLIST` conforme seus usuários autorizados.

## 3) Subir serviços
```bash
docker compose up -d --build
```

Serviços expostos:
- Backend: http://localhost:8000
- Frontend: http://localhost:3000
- Postgres: localhost:5432

## 3.1) Fluxo para avaliação em navegador
- Abra `http://localhost:3000/profile-preferences` para validar avatar por iniciais e troca de tema persistida em backend SQLite.
- Abra `http://localhost:3000/observability-dashboard` para validar carregamento do resumo de observabilidade.

## 4) Validar saúde básica
```bash
curl http://localhost:8000
```

Resposta esperada: JSON com `status: ok`.

## 5) Encerrar ambiente
```bash
docker compose down
```

## Observações importantes
- Este runbook sobe o ambiente-base executável para avaliação funcional em navegador.
- O backend utiliza bootstrap em PHP com persistência SQLite para suportar os fluxos do MVP nesta etapa.
- A integração Laravel completa permanece no roadmap técnico.
