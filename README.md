# PPCosta
PPCosta WebStore

## Estado atual

Fase 19 concluida: estrutura de pastas, base de dados SQL, autenticacao, FrontOffice, catalogo, produtos, personalizacao, carrinho, checkout, area cliente, administracao, gestao de produtos, gestao de clientes, gestao de encomendas, relatorios, SEO, testes, otimizacoes, seguranca e preparacao para producao.

## BackOffice

- Produtos: CRUD, duplicacao, ativacao/inativacao, remocao/arquivo, categorias, stock, SEO e CSV.
- Clientes: CRUD, pesquisa, filtros, bloqueio, newsletter, historico de compras, enderecos e notas internas.
- Encomendas: pesquisa, filtros por estado, detalhe, itens, moradas, valores, observacoes, linha temporal e atualizacao de estado.
- Relatorios: resumo financeiro, vendas mensais, estados, metodos, margens, stock baixo e exportacao CSV.
- SEO: meta tags, canonical, OpenGraph, Twitter cards, Schema.org, breadcrumbs, sitemap, robots.txt e URLs amigaveis.
- Testes: runner PHP nativo em `tests/run.php`, com cobertura de helpers, SEO, catalogo fallback, personalizacao, carrinho e CSRF.
- Otimizacoes: assets versionados, scripts diferidos, preconnect/dns-prefetch, cache HTTP para assets e compressao Apache.
- Seguranca: headers HTTP em PHP e Apache, CSP, HSTS em HTTPS, rate limit no login, protecao de uploads, CSRF e sessoes seguras.
- Producao: configuracao por variaveis de ambiente, `.env.example`, health check expandido e guia em `docs/PRODUCTION.md`.
- Desenvolvimento local: `router.php` permite testar URLs amigaveis com o servidor PHP embutido.

## Proximas fases

Projeto pronto para validacao final em ambiente real.
