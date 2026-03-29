# Checklist de Entrega — Cactus Financias (MVP)

## Desenvolvido até agora

### FUNC-09 — Preferências de Perfil
- [x] Endpoint autenticado para consultar preferência de tema (`GET /api/profile/theme`).
- [x] Endpoint autenticado para atualizar preferência de tema (`PATCH /api/profile/theme`).
- [x] Persistência de tema no cadastro de usuário (`theme_preference` em banco).
- [x] Service e Repository separados para regra de persistência.
- [x] Componente de avatar por iniciais sem upload de imagem.
- [x] Handler frontend para carregar tema persistido e atualizar tema.

### FUNC-10 — Painel de Observabilidade
- [x] Middleware de proteção por allowlist de infraestrutura.
- [x] Bloqueio com `403 Forbidden` para usuário não autorizado.
- [x] Validação por e-mail e/ou ID na allowlist.
- [x] Rota protegida para painel de observabilidade.
- [x] Documento de spike técnico da stack de observabilidade.

### Testes
- [x] Testes de feature para persistência de tema.
- [x] Testes de feature para leitura de tema persistido.
- [x] Testes de feature para acesso negado e acesso permitido por allowlist.

## Pendente para fechar a implementação executável

### Backend (bootstrap do projeto)
- [ ] Integrar os arquivos criados ao esqueleto Laravel completo (estrutura com `artisan`, providers e bootstrap real).
- [ ] Registrar middleware no fluxo oficial da aplicação (quando bootstrap completo estiver presente).
- [ ] Implementar view/página real do dashboard de observabilidade.

### Frontend (integração de aplicação)
- [ ] Integrar módulos de perfil em páginas reais do app (login/header/configurações).
- [ ] Aplicar tema carregado em runtime no layout global da aplicação.
- [ ] Conectar `AvatarInitials.vue` nos pontos reais de perfil/cabeçalho.

### Qualidade e pipeline
- [ ] Executar suíte automatizada completa em ambiente com Laravel/Nuxt configurados.
- [ ] Adicionar pipeline CI para testes backend/frontend.

## Regra operacional de acompanhamento
A partir desta entrega, todo reporte deve incluir obrigatoriamente:
1. Itens desenvolvidos no ciclo;
2. Itens pendentes;
3. Bloqueios técnicos (se existirem).
