# PPCosta
PPCosta WebStore

## Estado atual

Base funcional em desenvolvimento: loja, autenticacao, personalizacao, carrinho, checkout, area cliente e administracao. A revisao de 15/09/2026 corrigiu falhas funcionais, seguranca e adaptacao a dispositivos moveis. Ver `docs/REVIEW-2026-09-15.md` para cobertura e limitacoes.

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
- Emails: modulo transacional em `includes/mailer.php`, com log local em desenvolvimento e `mail()` em producao.
- QA: teste E2E HTTP em `tests/e2e_http.php` e limpeza em `tests/cleanup_qa.php`.
- Pagamentos: camada modular em `includes/payments.php`, transacoes em `payment_transactions` e migration `database/004_payments.sql`.

## Proximas fases

Concluir e testar as integracoes reais de pagamento, envio e faturacao antes de aceitar compras em producao.

## Personalizacao por produto

Em Produtos > Editar > Configurar personalizacoes, gerir campos ativos/obrigatorios,
limites de texto, ordem, acrescimos base, opcoes permitidas e precos especificos.
Preco de opcao em branco utiliza o preco global; zero substitui-o por zero.
O modo global acompanha todas as opcoes ativas, incluindo futuras adicoes.
O modo selecionado nunca acrescenta opcoes automaticamente. Uma selecao vazia
num campo obrigatorio impede a compra ate a configuracao ficar completa.
Fontes e posicoes devem ser previamente criadas na gestao global de personalizacao.

Instalacoes existentes: aplicar uma vez `database/008_product_personalization_settings.sql`
apos 006 e 007, antes de publicar este codigo. Nao reimportar 001 numa base existente.
Instalacoes novas: 001 ja inclui estas colunas; nao executar 008 novamente.
Validacao especifica: `php tests/product_personalization.php` (apenas desenvolvimento).
