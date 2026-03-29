#!/usr/bin/env bash
set -euo pipefail

echo "[1/3] Preparando arquivos .env..."
[ -f backend/.env ] || cp backend/.env.example backend/.env
[ -f frontend/.env ] || cp frontend/.env.example frontend/.env

echo "[2/3] Subindo containers..."
docker compose up -d --build

echo "[3/3] Verificando backend..."
curl -fsS http://localhost:8000 || true

echo "Sistema iniciado. Veja docs/runbook-subir-sistema.md"
