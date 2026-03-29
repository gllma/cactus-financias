# Como usar o CODEX neste projeto

## 1. Regra principal
O CODEX deve seguir estritamente o arquivo:

`docs/codex-instructions.md`

---

## 2. Prompt mínimo recomendado

Use sempre este formato:

```text
Leia e siga estritamente as instruções de docs/codex-instructions.md.

Tarefa desta rodada:
[DESCREVA A TAREFA AQUI]

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

---

## 3. Exemplos de tarefas

### Estrutura inicial
- Estruture o backend inicial em Laravel

### Funcionalidade
- Implemente persistência de tema no backend

### Frontend
- Implemente avatar por iniciais no frontend

### Segurança
- Implemente validação de allowlist no painel de observabilidade

---

## 4. Boas práticas

- Trabalhe sempre por etapas pequenas
- Não peça tudo de uma vez
- Sempre valide a resposta antes de seguir
- Use commits sugeridos pelo CODEX

---

## 5. Importante

Nunca peça para o CODEX:
- "melhorar" regras já definidas
- "simplificar" comportamento
- "adaptar" regra de negócio

Ele deve apenas implementar
