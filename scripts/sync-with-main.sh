#!/usr/bin/env bash
set -euo pipefail

# Sincroniza a branch atual com a main antes de abrir PR.
# Fluxo esperado:
# 1) atualizar refs remotas
# 2) garantir disponibilidade da main local/remota
# 3) aplicar rebase da branch atual sobre origin/main (ou main local)

current_branch="$(git rev-parse --abbrev-ref HEAD)"

if [ "$current_branch" = "main" ]; then
  echo "Você já está na branch main. Nenhuma sincronização necessária."
  exit 0
fi

if git remote get-url origin >/dev/null 2>&1; then
  git fetch origin --prune

  if git show-ref --verify --quiet refs/remotes/origin/main; then
    git rebase origin/main
    echo "Branch '$current_branch' sincronizada com origin/main via rebase."
    exit 0
  fi
fi

if git show-ref --verify --quiet refs/heads/main; then
  git rebase main
  echo "Branch '$current_branch' sincronizada com main local via rebase."
  exit 0
fi

echo "Não foi possível sincronizar: branch main não encontrada (local/remota)." >&2
exit 1
