# Diretrizes de Ambiente de Desenvolvimento e Hospedagem

Este documento complementa as instruções operacionais do projeto sem alterar qualquer regra funcional já validada.

## Ambiente de desenvolvimento
- O ambiente de desenvolvimento local deve considerar **WSL** como cenário padrão de uso.
- A configuração local deve priorizar compatibilidade com desenvolvimento em ambiente Linux dentro do Windows.
- Scripts, containers, dependências e instruções operacionais devem evitar dependências exclusivas de ambiente Windows nativo.
- Sempre que possível, comandos, paths e automações devem ser pensados para execução compatível com shell Linux.

## Hospedagem
- A aplicação deve ser planejada para hospedagem em uma **VPS Debian**.
- As decisões de infraestrutura, provisionamento, serviços, processos e automações devem considerar compatibilidade com Debian como ambiente-alvo principal.
- O setup de produção deve priorizar simplicidade operacional, previsibilidade, observabilidade e facilidade de manutenção.

## Diretrizes para o CODEX
Ao estruturar Docker, scripts, documentação operacional, provisionamento e artefatos de infraestrutura, o CODEX deve considerar obrigatoriamente:
- desenvolvimento local com foco em WSL;
- ambiente de execução e hospedagem com foco em VPS Debian;
- compatibilidade com fluxo Linux end-to-end;
- evitar decisões que assumam macOS ou Windows nativo como ambiente principal;
- manter aderência ao padrão estrutural de infraestrutura já definido para o projeto.
