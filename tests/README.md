# Testes PPCosta

Executar:

```bash
php tests/run.php
```

Os testes usam apenas PHP nativo e cobrem helpers, SEO, catalogo fallback, personalizacao, carrinho e CSRF.

Teste HTTP completo com BD e servidor local:

```bash
php -S 127.0.0.1:8000 router.php
php tests/e2e_http.php
```

Limpar clientes/encomendas QA criados pelos testes:

```bash
php tests/cleanup_qa.php
```
