# Instalação e Subida do Projeto — Cactus Financias (MVP)

## Objetivo
Este guia mostra o passo a passo para instalar dependências e subir o projeto para avaliação funcional no navegador.

## Pré-requisitos
- Git
- Docker + Docker Compose
- Node.js 22+ e npm 11+ (opcional para rodar frontend fora do container)
- PHP 8.3+ (opcional para validações locais)

## 1) Clonar o repositório
```bash
git clone <repo-url>
cd cactus-financias
```

## 2) Instalar dependências do frontend (opcional fora de container)
```bash
cd frontend
npm install
cd ..
```

## 3) Configurar variáveis de ambiente
```bash
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env
```

## 4) Subir o ambiente completo com Docker
```bash
docker compose up -d --build
```

## 5) Acessar no navegador
- Frontend: http://localhost:3000
- Perfil: http://localhost:3000/profile-preferences
- Observabilidade: http://localhost:3000/observability-dashboard
- Backend health: http://localhost:8000/health

## 6) Validar cenário funcional
1. No topo da aplicação, altere Nome/E-mail e clique em **Salvar sessão**.
2. Em Perfil, altere entre tema claro e escuro.
3. Em Observabilidade, altere a janela em minutos e clique em **Atualizar resumo**.
4. Para testar bloqueio da allowlist, use e-mail fora de `OBSERVABILITY_ALLOWLIST`.

## 7) Encerrar ambiente
```bash
docker compose down
```

## Atalho
Você pode usar:
```bash
./scripts/install-project.sh
./scripts/up-system.sh
```

## Solução de problemas
### npm install falha com 403
Seu ambiente pode estar bloqueando acesso ao registry. Configure proxy/espelho permitido pela sua rede.

### Docker não encontrado
Instale Docker Desktop/Engine e garanta `docker compose` disponível no terminal.

### main não encontrada ao sincronizar branch
O script `scripts/sync-with-main.sh` exige `main` local/remota configurada.
