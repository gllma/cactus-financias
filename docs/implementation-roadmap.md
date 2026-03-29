# Roadmap de Implementação — Cactus Financias (MVP)

## Fase 1 — Base de Perfil e Segurança (Concluída)
- [x] Persistência de tema em banco (`theme_preference`).
- [x] Endpoints de leitura/atualização de tema.
- [x] Avatar por iniciais no frontend (sem upload).
- [x] Proteção do painel de observabilidade por allowlist.

## Fase 2 — Observabilidade Inicial (Em andamento)
- [x] Dashboard web inicial com métricas resumidas.
- [x] Endpoint de API para resumo de observabilidade (consumo por frontend/custom dashboard).
- [x] Evoluir métricas do resumo (usuários totais, adoção de tema escuro e uptime simulado).

## Fase 3 — Integração de Aplicação (Pendente)
- [x] Conectar módulos frontend de perfil em página de preferências.
- [x] Aplicar tema persistido no layout global.
- [x] Integrar tela frontend inicial do painel de observabilidade.
- [x] Estruturar módulo frontend de observabilidade (DTO/Model/Service/Handler/Validator).

## Fase 4 — Qualidade e Entrega (Pendente)
- [ ] Ambiente Laravel/Nuxt completo para execução de testes fim a fim.
- [x] CI inicial com validação automatizada de sintaxe PHP backend.
- [x] CI com execução condicional de testes de feature quando PHPUnit estiver disponível.
- [x] Runbook operacional para subir ambiente local com Docker Compose.
- [ ] Hardening operacional de deploy/monitoramento.
