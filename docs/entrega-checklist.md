# Checklist de Entrega — Cactus Financias (MVP)

## Desenvolvido até agora

### FUNC-09 — Preferências de Perfil
- [x] Endpoint autenticado para consultar preferência de tema (`GET /api/profile/theme`).
- [x] Endpoint autenticado para atualizar preferência de tema (`PATCH /api/profile/theme`).
- [x] Persistência de tema no cadastro de usuário (`theme_preference` em banco).
- [x] Service e Repository separados para regra de persistência.
- [x] Componente de avatar por iniciais sem upload de imagem.
- [x] Handler frontend para carregar tema persistido e atualizar tema.
- [x] Handler frontend para aplicar tema persistido no layout global.

### FUNC-10 — Painel de Observabilidade
- [x] Middleware de proteção por allowlist de infraestrutura.
- [x] Bloqueio com `403 Forbidden` para usuário não autorizado.
- [x] Validação por e-mail e/ou ID na allowlist.
- [x] Rota protegida para painel de observabilidade.
- [x] Controller/Service/Repository para resumo inicial do dashboard de observabilidade.
- [x] View inicial do painel de observabilidade com métricas resumidas.
- [x] Endpoint de API para resumo do painel de observabilidade.
- [x] Módulo frontend de observabilidade com arquitetura por contexto.
- [x] Documento de spike técnico da stack de observabilidade.

### Testes
- [x] Testes de feature para persistência de tema.
- [x] Testes de feature para leitura de tema persistido.
- [x] Testes de feature para acesso negado e acesso permitido por allowlist.

## Pendente para fechar a implementação executável

### Backend (bootstrap do projeto)
- [ ] Integrar os arquivos criados ao esqueleto Laravel completo (estrutura com `artisan`, providers e bootstrap real).
- [ ] Registrar middleware no fluxo oficial da aplicação (quando bootstrap completo estiver presente).

### Frontend (integração de aplicação)
- [x] Integrar módulos de perfil em página de preferências.
- [x] Aplicar tema carregado em runtime no layout global da aplicação.
- [x] Conectar `AvatarInitials.vue` nos pontos reais de perfil/cabeçalho.
- [x] Integrar tela inicial de observabilidade no frontend.

### Qualidade e pipeline
- [ ] Executar suíte automatizada completa em ambiente com Laravel/Nuxt configurados.
- [x] Adicionar pipeline CI inicial para validação de sintaxe PHP backend.
- [x] Adicionar execução condicional de testes de feature no CI quando PHPUnit estiver disponível.
- [x] Disponibilizar runbook e script para subir ambiente local.
- [x] Disponibilizar documentação consolidada de instalação e subida do projeto.
- [x] Unificar documentação Docker em guia único de instalação/deploy/operação.
- [x] Disponibilizar fluxo navegável para avaliação funcional em navegador.
- [x] Permitir simulação de usuários no frontend para validar allowlist e tema por sessão.
- [x] Garantir estratégia Docker-first (build e subida sem instalação local de dependências da aplicação).
- [x] Centralizar processos de build/deploy/operação no Makefile.
- [x] Garantir `make install` para instalação completa e `make deploy` para atualização contínua.
- [x] Disponibilizar entrada facilitada nos containers via Makefile (`make in`).

## Regra operacional de acompanhamento
A partir desta entrega, todo reporte deve incluir obrigatoriamente:
1. Itens desenvolvidos no ciclo;
2. Itens pendentes;
3. Bloqueios técnicos (se existirem).
4. Confirmação de sincronização da branch com a `main` antes de abrir PR.
