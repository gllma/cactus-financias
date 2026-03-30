NUNCA use valores hardcoded para lógica de negócio.

- Prazos, limites, thresholds → `config('vivamobil.chave', default)`
- Valores runtime ajustáveis → tabela `admin.settings` via `SettingsEnum`
- Credenciais, URLs → `.env`
- Paginação → `config('vivamobil.pagination.per_page', 15)`
- Textos de resposta/exceção → `__('modulo.chave')` (lang files)

Se precisar de um valor novo, adicione em `config/vivamobil.php` com fallback sensato.
