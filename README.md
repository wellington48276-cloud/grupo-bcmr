# Grupo BCMR — Sistema Integrado V6

Versão consolidada em **PHP + MySQL + JavaScript/PWA** para as três empresas:

1. **Comercial Marques** — produtos, estoque, pedidos, pagamentos e documentos.
2. **Manutenções Marques** — serviços, protocolos, histórico, orçamentos e documentos.
3. **Grupo BCMR Transportes** — viagens, acompanhamento e alertas. Há apenas um motorista, por isso **não existe cadastro nem seleção de motorista**.

O MySQL é a fonte oficial. Google Sheets é somente relatório/histórico.

## V5 — fluxo completo incluído

### Site público

Acesse `public/index.php`.

O cliente pode:

- visualizar catálogo disponível;
- adicionar produtos ao carrinho;
- criar pedido;
- gerar reserva de estoque por 30 minutos;
- escolher PIX/cartão/link de pagamento;
- solicitar manutenção e receber protocolo;
- consultar protocolo e histórico;
- solicitar viagem e receber código;
- consultar viagem e histórico.

Orçamentos podem ser respondidos em `public/orcamento.php?token=...`.

### Comercial Marques

- Cadastro de produtos.
- Entrada, saída, perda, devolução e ajuste de estoque.
- Histórico de movimentações.
- Pedido público com `SELECT ... FOR UPDATE` para impedir venda concorrente da mesma unidade.
- Reserva de estoque por 30 minutos.
- `jobs/expirar_reservas.php` devolve automaticamente o estoque de pedidos não pagos.
- Aprovação do pagamento confirma a reserva e marca o pedido como pago.
- Recusa/cancelamento devolve o estoque.
- Pagamentos com idempotência e webhook HMAC.

### Manutenções Marques

- Criação de serviço e protocolo.
- Timeline/histórico.
- Prazos e alertas.
- Orçamento com mão de obra + peças.
- Token público de aprovação/recusa.
- Aprovação move o serviço para `EM_MANUTENCAO`.
- Recusa move para `CANCELADO`.

### Transportes BCMR

Fluxo simplificado para um único motorista:

`AGENDADA → A_CAMINHO → EMBARQUE → EM_VIAGEM → FINALIZADA`

Também existe `CANCELADA`.

- Não há tabela, tela nem seleção de motorista.
- Alertas de viagem próxima e atrasada.
- Histórico completo de status.

### Gestão avançada

Acesse `gestao.php` no ADM para:

- pedidos e reservas;
- orçamentos;
- faturamento consolidado dos últimos 12 meses;
- status das integrações.

### Documentos e NF-e

- PDF, XML, JPG, PNG, WebP e TXT.
- Limite de 15 MB.
- Nome físico aleatório.
- SHA-256.
- Download autenticado.
- XML de NF-e com extração local.
- PDF/foto pode ser encaminhado para um provedor de IA quando configurado.
- IA nunca altera estoque automaticamente: dados extraídos precisam ser revisados.

### Notificações / push

- Eventos centrais para as três empresas.
- Prioridades `INFORMATIVO`, `ATENCAO`, `CRITICO`.
- Chaves únicas evitam repetição de alertas críticos.
- Fila `push_envios` preparada para Firebase Cloud Messaging.

### Google Sheets

O job `jobs/sincronizar_google_sheets.php` usa `integracao_sheets` como outbox.

O Apps Script em `google_apps_script/Code.gs` cria/atualiza abas:

- Dashboard Geral
- Estoque Atual
- Movimentações Estoque
- Vendas
- Pagamentos
- Serviços
- Orçamentos
- Viagens
- Eventos Integração

A indisponibilidade do Google **não interrompe vendas, serviços ou viagens**.

## Instalação nova

1. PHP 8.1+ recomendado, MySQL 8+ e servidor HTTPS.
2. Configure as variáveis de ambiente com base em `config/.env.example`.
3. Importe `sql/install.sql`.
4. Garanta escrita em `storage/uploads` e `storage/` para logs.
5. Crie o primeiro administrador com `setup_admin.php` via terminal.
6. Configure o cron usando `cron.example`.
7. Acesse `login.php` para o ADM e `public/index.php` para o site público.

## Atualizando da V4

Faça backup do banco e execute **uma vez**:

```bash
mysql -u SEU_USUARIO -p bcmr < sql/migrate_v4_to_v5.sql
```

## Variáveis principais

```bash
BCMR_DB_HOST=127.0.0.1
BCMR_DB_PORT=3306
BCMR_DB_NAME=bcmr
BCMR_DB_USER=bcmr_app
BCMR_DB_PASS=senha_forte
BCMR_WEBHOOK_SECRET=segredo_webhook
BCMR_JOB_TOKEN=segredo_jobs
BCMR_SHEETS_WEBHOOK=https://script.google.com/macros/s/.../exec
BCMR_SHEETS_TOKEN=segredo_sheets
FCM_PROJECT_ID=projeto-firebase
FCM_BEARER_TOKEN=token-oauth
BCMR_AI_DOCUMENT_ENDPOINT=https://seu-provedor/extrair
BCMR_AI_DOCUMENT_KEY=chave
```

Não coloque chaves diretamente no repositório.

## Pagamentos externos

O núcleo está pronto, mas **não existe integração real fingida** com Mercado Pago, InfinitePay, PagBank, Ton, Getnet, SafraPay, StafBank, SumUp ou Cielo sem credenciais e documentação da conta.

O contrato pronto é:

1. criar pagamento no sistema;
2. enviar para o adaptador do gateway;
3. receber identificador externo;
4. validar assinatura do webhook;
5. conferir valor;
6. processar idempotentemente;
7. confirmar ou devolver a reserva de estoque.

## O que falta para produção real

Somente itens que dependem do ambiente/contas reais:

- domínio e HTTPS;
- usuário/senha do MySQL;
- credenciais do gateway escolhido;
- credenciais Firebase;
- implantação do Apps Script/Google Sheet;
- credenciais do provedor de IA, se desejado;
- identidade visual/logos definitivos;
- testes de homologação e backup do servidor.

O código não deve marcar nenhuma dessas integrações como “ativa” até as credenciais reais serem configuradas.

---

## V6 — alimentação automática das abas do Google Planilhas

Nesta versão o cadastro é feito sempre pelo APP/ site e o **MySQL continua sendo a fonte oficial**. A planilha é alimentada automaticamente pela fila `integracao_sheets`.

### Roteamento automático

| Ação no sistema | Aba(s) atualizadas |
|---|---|
| Cadastrar/alterar produto | `Catálogo` + `Estoque Atual` |
| Entrada, venda, devolução ou ajuste de estoque | `Movimentações Estoque` + `Estoque Atual` |
| Criar/alterar pedido | `Vendas` |
| Criar/confirmar/cancelar pagamento | `Pagamentos` + `Financeiro` + atualização de `Vendas` |
| Abrir/alterar serviço | `Serviços` + `Serviços Histórico` |
| Criar/responder orçamento | `Orçamentos` |
| Criar/alterar viagem | `Viagens` + `Viagens Histórico` |
| Finalizar viagem com valor | `Financeiro` |
| Finalizar serviço com valor | `Financeiro` |
| Enviar documento | `Documentos` quando enfileirado |

O Apps Script usa **UPSERT** nas abas de estado atual (não cria linhas duplicadas do mesmo produto/serviço/viagem) e **APPEND idempotente** nas abas históricas. A aba oculta `_SYNC` registra cada `fila_id` já recebida e impede duplicação quando o job PHP repete uma tentativa.

### Configuração

1. Crie uma Google Planilha para o Grupo BCMR.
2. Abra **Extensões > Apps Script** e cole `google_apps_script/Code.gs`.
3. Preencha `SPREADSHEET_ID` e troque `BCMR_TOKEN` por um token forte.
4. Publique o Apps Script como Web App.
5. Configure no servidor:

```bash
export BCMR_SHEETS_WEBHOOK="URL_DO_WEB_APP"
export BCMR_SHEETS_TOKEN="MESMO_TOKEN_DO_APPS_SCRIPT"
```

6. Execute/agende `jobs/sincronizar_google_sheets.php`.

Para verificar a fila via APP/API, ADMIN_GERAL pode consultar:

```text
/api/integracoes/sheets_status.php
```

### Importante

Se alguém apagar ou editar uma célula da planilha, isso **não altera o MySQL**. Na próxima sincronização de uma entidade atualizável, o sistema pode novamente refletir o estado correto naquela linha. Estoque, pedidos, serviços e viagens devem ser alterados sempre pelo APP/API.
