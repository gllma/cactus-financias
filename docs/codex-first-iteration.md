# Prompt para Primeira Iteração do CODEX

Este documento define exatamente como iniciar o desenvolvimento do projeto de forma controlada.

## Instrução obrigatória

```text
Leia e siga estritamente:
- docs/codex-instructions.md
- docs/development-environment.md
- docs/implementation-roadmap.md

Tarefa desta rodada:
Iniciar o desenvolvimento do projeto executando a Fase 1 — Fundação técnica.

O foco desta etapa NÃO é implementar funcionalidades de usuário, avatar, tema ou preferências.

O foco é construir a base do sistema.

Implemente obrigatoriamente:
- bootstrap do backend Laravel
- estrutura arquitetural base (Controller → Service → Repository → Model)
- configuração inicial de ambiente
- estrutura Docker para desenvolvimento em WSL
- preparação para execução em VPS Debian
- definição base de banco de dados
- estrutura inicial de autenticação
- base estrutural para multitenancy

Restrições obrigatórias:
- não avançar para preferências de perfil
- não implementar avatar
- não implementar tema
- não avançar para observabilidade

Responda obrigatoriamente com:
1. Resumo do que está sendo implementado
2. Arquivos criados
3. Arquivos alterados
4. Justificativa arquitetural
5. Código
6. Testes
7. Passos para execução
8. Riscos, observações e pendências técnicas
9. Sugestão de commits
```

## Objetivo
Garantir que o CODEX comece pela base correta e não avance prematuramente para partes periféricas do sistema.
