<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Painel de Observabilidade</title>
  </head>
  <body>
    <main>
      <h1>Painel de Observabilidade</h1>
      <section>
        <p>Falhas em jobs: {{ $summary['failed_jobs'] }}</p>
        <p>Jobs pendentes: {{ $summary['pending_jobs'] }}</p>
        <p>Exceções recentes (60 min): {{ $summary['recent_exceptions'] }}</p>
      </section>
    </main>
  </body>
</html>
