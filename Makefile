SHELL := /bin/bash
.DEFAULT_GOAL := help

COMPOSE := docker compose
BACKEND_ENV := backend/.env
FRONTEND_ENV := frontend/.env
SERVICE ?= backend
NODE_IMAGE := node:22-alpine

.PHONY: help setup install build deploy deploy-backend deploy-frontend up down restart logs ps health lint sync ci-test clean in in-backend in-frontend in-db doctor rebuild

help: ## Exibe comandos disponíveis
	@echo "Comandos disponíveis:"
	@grep -E '^[a-zA-Z_-]+:.*?## ' Makefile | awk 'BEGIN {FS = ":.*?## "}; {printf "  %-16s %s\n", $$1, $$2}'

setup: ## Cria .env de backend/frontend se necessário
	@[ -f $(BACKEND_ENV) ] || cp backend/.env.example $(BACKEND_ENV)
	@[ -f $(FRONTEND_ENV) ] || cp frontend/.env.example $(FRONTEND_ENV)
	@echo "Arquivos .env prontos"

build: setup ## Builda todas as imagens Docker
	$(COMPOSE) build

install: setup build up health ## Instala completamente e valida o projeto
	@echo "Instalação completa finalizada"

deploy: setup ## Atualiza frontend/backend e roda comandos pós-deploy
	$(COMPOSE) up -d --build --force-recreate --remove-orphans
	@$(MAKE) deploy-backend
	@$(MAKE) deploy-frontend
	@$(MAKE) health
	@echo "Deploy atualizado concluído"

deploy-backend: ## Executa optimize:clear e migrate no backend (quando Laravel estiver disponível)
	@$(COMPOSE) exec -T backend sh -lc '
		set -e; \
		if [ -f backend/artisan ]; then \
			php backend/artisan optimize:clear; \
			php backend/artisan migrate --force; \
		else \
			echo "[deploy-backend] backend/artisan não encontrado; pulando optimize:clear e migrate."; \
		fi'

deploy-frontend: ## Executa npm install + npm run build do frontend em container Node
	@docker run --rm \
		-v "$(PWD)/frontend:/app" \
		-w /app \
		$(NODE_IMAGE) sh -lc 'npm install --no-audit --no-fund && npm run build'

up: setup ## Sobe todo o ambiente
	$(COMPOSE) up -d --build

down: ## Derruba o ambiente
	$(COMPOSE) down

restart: down up ## Reinicia ambiente completo

logs: ## Exibe logs de todos os serviços
	$(COMPOSE) logs -f

ps: ## Lista status dos serviços
	$(COMPOSE) ps

health: ## Verifica health do backend
	@curl -fsS http://localhost:8000/health || (echo "Backend indisponível" && exit 1)

lint: ## Roda lint de sintaxe PHP do backend
	@for f in $$(find backend -name '*.php'); do php -l "$$f"; done

sync: ## Sincroniza branch com main (quando disponível)
	./scripts/sync-with-main.sh

ci-test: ## Executa testes de feature quando PHPUnit existir
	@if [ -x backend/vendor/bin/phpunit ]; then \
		cd backend && ./vendor/bin/phpunit --testsuite=Feature; \
	else \
		echo "PHPUnit não encontrado em backend/vendor/bin/phpunit; etapa ignorada."; \
	fi

clean: ## Remove containers e volumes
	$(COMPOSE) down -v

rebuild: clean build up ## Rebuild completo do ambiente

doctor: ## Diagnóstico rápido do ambiente (status + logs)
	$(COMPOSE) ps
	$(COMPOSE) logs --tail=120 backend frontend db

in: ## Entra no container informado em SERVICE (default: backend)
	$(COMPOSE) exec $(SERVICE) sh

in-backend: ## Entra no container backend
	$(COMPOSE) exec backend sh

in-frontend: ## Entra no container frontend
	$(COMPOSE) exec frontend sh

in-db: ## Entra no container db
	$(COMPOSE) exec db sh
