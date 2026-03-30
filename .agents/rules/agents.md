## Agents e Worktrees

Worktrees isolados (`isolation: worktree`) têm problemas de permissão para Write/Bash neste projeto. As ferramentas de escrita são negadas pelo sandbox independentemente do modo de permissão configurado.

### Quando usar worktree
- Agents read-only: `reviewer`, `planner`, `Explore`
- Pesquisa paralela sem escrita

### Quando NÃO usar worktree
- Agents que precisam criar/editar arquivos (`backend`, `tester`)
- Qualquer tarefa que envolva `Write`, `Edit` ou `Bash`

### Estratégia para implementação paralela
1. Implementar sequencialmente no projeto principal (mais confiável)
2. OU usar branches separadas via `git checkout -b` + merge manual
3. Agents de escrita sem `isolation: worktree` funcionam no projeto principal

### Tester
- Usar como agent general-purpose (sem `subagent_type: tester`)
- O `subagent_type: tester` com worktree falha
- Sem worktree funciona, mas cuidado com conflitos de arquivo

### Sequência recomendada para times
1. Backend: implementar direto (sequencial ou paralelo sem worktree)
2. Tester: lançar APÓS backend terminar (depende dos arquivos criados)
3. Reviewer: pode rodar em paralelo com tester (read-only, worktree OK)
