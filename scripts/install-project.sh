#!/usr/bin/env bash
set -euo pipefail

echo "[1/2] Preparando .env..."
[ -f backend/.env ] || cp backend/.env.example backend/.env
[ -f frontend/.env ] || cp frontend/.env.example frontend/.env

echo "[2/2] Instalando dependências frontend..."
(
  cd frontend
  npm install
)

echo "Instalação concluída. Execute: ./scripts/up-system.sh"
