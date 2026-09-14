# Preparacao para producao

## Requisitos

- PHP 8.x com PDO MySQL ativo.
- MySQL ou MariaDB.
- Apache com `mod_rewrite`, `mod_headers` e `mod_deflate`, ou regras equivalentes no servidor escolhido.
- Diretorio `uploads/` gravavel pelo utilizador do servidor web.

## Configuracao

Definir variaveis de ambiente com base em `.env.example`.

Valores minimos:

- `APP_ENV=production`
- `APP_URL=https://dominio-final`
- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
- `UPLOAD_DIR` com caminho absoluto gravavel

## Base de dados

Executar por ordem:

```bash
mysql -u utilizador -p < database/001_schema.sql
mysql -u utilizador -p ppcosta_store < database/002_seed.sql
mysql -u utilizador -p ppcosta_store < database/003_views_triggers_procedures.sql
```

## Validacao antes de publicar

```bash
find . -name '*.php' -maxdepth 3 -print0 | xargs -0 -n1 php -l
php tests/run.php
php sitemap.php
```

Para testar localmente com URLs amigaveis:

```bash
php -S 127.0.0.1:8000 router.php
```

Abrir `https://dominio-final/api/health.php` e confirmar:

- `status: ok`
- `database: ok`
- `uploads_writable: true`

## Checklist

- Alterar `APP_URL` para o dominio final.
- Confirmar HTTPS ativo.
- Confirmar permissao de escrita em `uploads/`.
- Confirmar que `/includes`, `/database`, `/admin` e `/api` seguem as regras de acesso pretendidas.
- Criar a primeira conta; essa conta recebe permissao de administrador.
- Testar checkout completo.
- Testar upload de personalizacao.
- Submeter `sitemap.php` ou `sitemap.xml` na Search Console.
