# Roadmap de Implementação do Cactus Financias (MVP)

Este documento define a **ordem obrigatória de execução** do desenvolvimento do projeto pelo CODEX.

Seu objetivo é evitar que a IA escolha arbitrariamente um recorte funcional isolado e avance em partes secundárias antes da fundação técnica do sistema.

## Regra principal
O CODEX deve seguir este roadmap em conjunto com:
- `docs/codex-instructions.md`
- `docs/development-environment.md`

Se houver conflito entre conveniência de implementação e a ordem definida abaixo, a ordem deste roadmap deve prevalecer.

---

# Fase 1 — Fundação técnica do projeto

## Objetivo
Construir a base estrutural do sistema, garantindo que o projeto esteja preparado para evoluir com segurança, consistência arquitetural e aderência ao ambiente operacional definido.

## Escopo da fase
- bootstrap inicial do backend em Laravel;
- organização arquitetural base seguindo Controller → Service → Repository → Model;
- estrutura inicial de configuração de ambiente;
- Docker e artefatos de infraestrutura para desenvolvimento em WSL;
- preparação estrutural para hospedagem em VPS Debian;
- definição da base de banco, cache, filas e serviços auxiliares;
- configuração inicial de autenticação;
- preparação estrutural inicial para multitenancy.

## Restrições da fase
Nesta fase, o CODEX **não deve priorizar**:
- preferências de perfil;
- avatar;
- persistência de tema;
- observabilidade visual;
- relatórios ricos;
- funcionalidades periféricas de interface.

Esses itens só podem ser tocados nesta fase se forem estritamente necessários para fechar dependências estruturais críticas.

---

# Fase 2 — Base funcional de autenticação e contexto do usuário

## Objetivo
Consolidar o núcleo de autenticação, sessão, identidade do usuário e isolamento inicial por contexto/tenant.

## Escopo da fase
- autenticação e segurança base;
- cadastro/estrutura base de usuários;
- sessão/autorização inicial;
- integração inicial entre usuário autenticado e espaço/contexto;
- fundamentos de multitenancy necessários para os módulos do MVP.

## Restrições da fase
Nesta fase, o CODEX ainda não deve priorizar acabamento de preferências visuais ou melhorias cosméticas.

---

# Fase 3 — Estrutura inicial do frontend

## Objetivo
Criar a base do frontend em Vue/Nuxt respeitando integralmente os padrões corporativos.

## Escopo da fase
- bootstrap do frontend em Vue/Nuxt;
- organização modular por contexto;
- configuração inicial de clients, composables, stores e layouts;
- estrutura base para autenticação no frontend;
- integração inicial com backend.

## Restrições da fase
Não priorizar ainda regras visuais específicas de perfil, avatar ou tema, salvo quando forem dependências inevitáveis da arquitetura base.

---

# Fase 4 — Núcleo funcional do MVP

## Objetivo
Implementar os módulos centrais do produto que sustentam a operação principal do sistema.

## Escopo da fase
- dashboard consolidado;
- gestão de espaços multitenancy;
- cofres e categorias com soft delete;
- lançamentos avulsos e programados.

---

# Fase 5 — Operações complementares do MVP

## Objetivo
Implementar as funcionalidades operacionais e gerenciais que complementam o núcleo do produto.

## Escopo da fase
- central de notificações;
- edição e aprovações em lote;
- extratos;
- relatórios dinâmicos;
- exportação rica.

---

# Fase 6 — Administração e preferências de perfil

## Objetivo
Implementar as funcionalidades administrativas e as preferências de usuário já definidas no escopo.

## Escopo da fase
- administração de usuários por espaço e permissões;
- avatar automático por iniciais;
- persistência de tema no banco de dados;
- preferências de perfil.

## Observação importante
Os itens abaixo pertencem explicitamente a esta fase e **não devem ser tratados como prioridade de início do projeto**:
- avatar por iniciais;
- tema claro/escuro persistido;
- refinamentos de perfil do usuário.

---

# Fase 7 — Observabilidade e acesso por allowlist

## Objetivo
Concluir o MVP com a camada de observabilidade e o controle de acesso específico já validado.

## Escopo da fase
- spike técnico de observabilidade;
- avaliação entre stack Laravel e solução customizada;
- implementação da allowlist de acesso ao painel de observabilidade;
- retorno 403 para usuários não autorizados.

---

# Regra de execução por rodada
Sempre que receber uma tarefa ampla como “comece o desenvolvimento” ou “avance no projeto”, o CODEX deve:
1. identificar em qual fase do roadmap o projeto se encontra;
2. executar primeiro os itens ainda pendentes da fase atual;
3. não saltar para fases posteriores sem justificativa técnica explícita;
4. deixar claro na resposta qual fase está sendo trabalhada.

---

# Regra de prioridade
A ordem de prioridade obrigatória é:
1. fundação técnica;
2. autenticação e contexto base;
3. frontend base;
4. núcleo funcional do MVP;
5. operações complementares;
6. administração e preferências de perfil;
7. observabilidade.

---

# Regra de controle de escopo
Se a tarefa solicitada pelo usuário for ampla, o CODEX deve interpretar que a prioridade inicial é sempre a **Fase 1**.

Somente após a estabilização da base estrutural o desenvolvimento deve avançar para as demais fases.
