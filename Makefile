SHELL := /bin/bash
.DEFAULT_GOAL := help

COMPOSE := docker compose
BACKEND_ENV := backend/.env
FRONTEND_ENV := frontend/.env

.PHONY: help setup build up down restart logs ps health lint sync deploy install ci-test clean in in-backend in-frontend in-db

SERVICE ?= backend

help: ## Exibe comandos disponíveis
	@echo "Comandos disponíveis:"
	@grep -E '^[a-zA-Z_-]+:.*?## ' Makefile | awk 'BEGIN {FS = ":.*?## "}; {printf "  %-12s %s\n", $$1, $$2}'

setup: ## Cria .env de backend/frontend se necessário
	@[ -f $(BACKEND_ENV) ] || cp backend/.env.example $(BACKEND_ENV)
	@[ -f $(FRONTEND_ENV) ] || cp frontend/.env.example $(FRONTEND_ENV)
	@echo "Arquivos .env prontos"

build: setup ## Builda todas as imagens Docker
	$(COMPOSE) build

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

install: build ## Alias de instalação via Docker

ci-test: ## Executa testes de feature quando PHPUnit existir
	@if [ -x backend/vendor/bin/phpunit ]; then \
		cd backend && ./vendor/bin/phpunit --testsuite=Feature; \
	else \
		echo "PHPUnit não encontrado em backend/vendor/bin/phpunit; etapa ignorada."; \
	fi

deploy: build up health ## Pipeline local de build+subida+healthcheck
	@echo "Deploy local concluído"

clean: ## Remove containers e volumes
	$(COMPOSE) down -v

in: ## Entra no container informado em SERVICE (default: backend)
	$(COMPOSE) exec $(SERVICE) sh

in-backend: ## Entra no container backend
	$(COMPOSE) exec backend sh

in-frontend: ## Entra no container frontend
	$(COMPOSE) exec frontend sh

in-db: ## Entra no container db (psql shell)
	$(COMPOSE) exec db sh
