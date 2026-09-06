# Base de dados PPCosta

Scripts SQL para MySQL/MariaDB.

## Ordem de importacao

1. `001_schema.sql`
2. `002_seed.sql`
3. `003_views_triggers_procedures.sql`

## Notas

- O esquema usa `utf8mb4` e `InnoDB`.
- As tabelas principais incluem chaves primarias, estrangeiras, indices e constraints.
- Passwords devem ser guardadas com `password_hash()` no PHP.
- Valores monetarios usam `DECIMAL(12,2)`.
- Uploads guardam metadados na base de dados e ficheiros no diretorio `/uploads`.
