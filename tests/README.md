# Testes PPCosta

Executar:

```bash
php tests/run.php
```

Os testes usam PHP nativo. `run.php` cobre helpers, SEO, calculos, personalizacao,
carrinho e CSRF. `review.php` acrescenta regressao com MySQL e HTTP.

Teste HTTP completo com BD e servidor local:

```bash
php -S 127.0.0.1:8000 router.php
php tests/e2e_http.php
```

`e2e_http.php` executa a bateria de `review.php`, com produtos, utilizadores,
encomendas e uploads temporarios. A limpeza e automatica e limitada aos dados
criados pelo teste. Exige ambiente de desenvolvimento e emails em modo `log`.

Limpar clientes/encomendas QA criados pelos testes:

```bash
php tests/cleanup_qa.php
```
