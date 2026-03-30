PostgreSQL 17 + PostGIS. SEMPRE usar o schema correto nas migrations e queries:

- `admin.*` → users, roles, permissions, settings, reset_password_sessions, user_statuses, user_has_statuses, verify_email_sessions
- `social.*` → people, addresses, contacts, contact_types, address_aliases
- `trip.*` → routes, route_statuses, route_has_statuses, route_points, trip_bookings, trip_booking_statuses, trip_booking_has_statuses, trip_booking_routes, trip_booking_route_statuses, trip_booking_routes_has_statuses, trip_booking_points, trip_booking_point_types, trip_booking_point_statuses, trip_booking_point_has_statuses, trip_reasons, departure_cancellation_reasons, booking_categories
- `public.*` → personal_access_tokens, refresh_tokens, bus_seat_types, buses, migrations

Exemplo: `Schema::create('trip.routes', function (Blueprint $table) { ... });`

## Colunas — Tabelas Principais

Ao escrever queries SQL, SEMPRE verificar as colunas reais via migrations. Tabelas que causam erro comum:

| Tabela | Colunas | NÃO TEM |
|---|---|---|
| `admin.users` | id, username, password, active, person_id, last_status_id | **`created_at`**, timestamps |
| `social.people` | id, name, cpf, avatar_name, priority_code, bus_seat_type_id, default_address_id | |
| `social.addresses` | id, nickname, cep, address, number, complement, reference_point, addressable_type, addressable_id, neighborhood, city, state | **`latitude`**, **`longitude`** |
| `public.buses` | id, name, description, active, created_at, updated_at | **`plate`** |
| `trip.trip_reasons` | id, name, description, slug, created_at, updated_at | **`priority_weight`** |
| `trip.trip_booking_points` | id, trip_booking_point_type_id, driver_arrived, trip_booking_route_id, address (jsonb), created_at, updated_at | **`person_id`**, **`address_id`** |

> **Regra:** Antes de escrever uma query SQL com SELECT, verificar as colunas reais consultando as migrations ou rodando `information_schema.columns`. NÃO assumir que uma coluna existe.

## Validation Rules com Schemas

**ARMADILHA:** Laravel interpreta `exists:schema.table,column` como `connection=schema, table=table`. Isso gera "Database connection [schema] not configured."

```php
// ERRADO — Laravel interpreta 'public' como connection name
'bus_id' => ['required', 'integer', 'exists:public.buses,id'],

// CERTO — usar nome da tabela sem schema (search_path inclui todos os schemas)
'bus_id' => ['required', 'integer', 'exists:buses,id'],
```

O `search_path` em `config/database.php` inclui `public,admin,social,trip`, então tabelas de qualquer schema são acessíveis pelo nome.

**Mesma regra para `unique:`**:
```php
// ERRADO
'username' => 'unique:admin.users,username'

// CERTO
'username' => 'unique:users,username'
```

## PostGIS e Geoespacial

Usar tipos nativos para dados geoespaciais (`$table->point()`, `$table->geometry()`), NUNCA string ou json.
Stored procedures: usadas para auth (`admin.process_login`, `admin.update_last_access`).

## Banco de testes

- Banco: `vivamobil_api_testing` (PostgreSQL, NÃO SQLite)
- Configurado em `.env.testing` e `phpunit.xml`
- `make pest` recria o banco de testes automaticamente antes de rodar
- `CACHE_STORE=array` — NUNCA usar `file` (causa contaminação entre testes)
- `DatabaseTransactions` (não `RefreshDatabase`) em `tests/Pest.php`
- Extensões habilitadas: PostGIS, pg_trgm
- Stored procedures disponíveis no banco de testes
