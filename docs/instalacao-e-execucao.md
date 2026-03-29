# Instalação e Subida do Projeto — Cactus Financias (MVP)

## Objetivo
Subir o projeto **somente com Docker**, sem instalar dependências de aplicação na máquina local, orquestrando tudo via `Makefile`.

## Pré-requisitos
- Git
- Docker + Docker Compose
- Make

## 1) Clonar o repositório
```bash
git clone <repo-url>
cd cactus-financias
```

## 2) Buildar imagens
```bash
make build
```

## 3) Subir o ambiente completo
```bash
make up
```

## 4) Acessar no navegador
- Frontend: http://localhost:3000
- Perfil: http://localhost:3000/profile-preferences
- Observabilidade: http://localhost:3000/observability-dashboard
- Backend health: http://localhost:8000/health

## 5) Validar cenário funcional
1. No topo da aplicação, altere Nome/E-mail e clique em **Salvar sessão**.
2. Em Perfil, altere entre tema claro e escuro.
3. Em Observabilidade, altere a janela em minutos e clique em **Atualizar resumo**.
4. Para testar bloqueio da allowlist, use e-mail fora de `OBSERVABILITY_ALLOWLIST`.

## 6) Comandos úteis do Makefile
```bash
make help
make ps
make logs
make lint
make deploy
make down
make in SERVICE=backend
```

## Solução de problemas
### Docker não encontrado
Instale Docker Desktop/Engine e garanta `docker compose` disponível no terminal.

### Make não encontrado
Instale `make` no sistema e execute novamente os comandos.

### Falha ao buildar frontend
Verifique se o Docker está com permissão para buildar a imagem e se o contexto do projeto foi copiado corretamente.

### main não encontrada ao sincronizar branch
Execute `make sync` somente quando existir branch `main` local/remota.
