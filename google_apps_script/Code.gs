const BCMR_TOKEN = 'TROQUE_POR_UM_TOKEN_FORTE';
const SPREADSHEET_ID = 'COLE_AQUI_O_ID_DA_PLANILHA';

function doPost(e) {
  try {
    const token = e && e.parameter ? (e.parameter.token || '') : '';
    if (BCMR_TOKEN && token !== BCMR_TOKEN) return json({status:false,mensagem:'Token inválido'});

    const p = JSON.parse((e.postData && e.postData.contents) || '{}');
    if (!p.fila_id || !p.entidade_tipo) return json({status:false,mensagem:'Payload incompleto'});

    const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
    prepararEstrutura(ss);

    if (filaJaProcessada(ss, p.fila_id)) {
      return json({status:true,mensagem:'Fila já processada',fila_id:p.fila_id});
    }

    processarPayload(ss, p);
    registrarFila(ss, p);
    atualizarDashboard(ss);
    return json({status:true,fila_id:p.fila_id,aba:p.aba_destino || ''});
  } catch (err) {
    return json({status:false,mensagem:String(err && err.message ? err.message : err)});
  }
}

function processarPayload(ss, p) {
  const d = p.dados || {};
  const sync = new Date();

  if (p.entidade_tipo === 'PRODUTO') {
    upsert(ss, 'Catálogo', ['ID','SKU','Produto','Categoria','Marca','Modelo','Descrição','Preço Compra','Preço Venda','Ativo','Atualizado'], d.id,
      [d.id,d.sku,d.nome,d.categoria,d.marca,d.modelo,d.descricao,d.preco_compra,d.preco_venda,d.ativo,sync]);
    upsert(ss, 'Estoque Atual', ['ID','SKU','Produto','Categoria','Estoque','Mínimo','Situação','Preço Venda','Atualizado'], d.id,
      [d.id,d.sku,d.nome,d.categoria,d.estoque_atual,d.estoque_minimo,situacaoEstoque(d.estoque_atual,d.estoque_minimo),d.preco_venda,sync]);
    return;
  }

  if (p.entidade_tipo === 'ESTOQUE_MOVIMENTACAO') {
    appendOnce(ss, 'Movimentações Estoque', ['Fila','Data','ID Mov.','Produto ID','SKU','Produto','Categoria','Tipo','Quantidade','Antes','Depois','Referência','Observação'], p.fila_id,
      [p.fila_id,d.criado_em,d.id,d.produto_id,d.sku,d.produto,d.categoria,d.tipo,d.quantidade,d.estoque_anterior,d.estoque_posterior,d.referencia,d.observacao]);
    upsert(ss, 'Estoque Atual', ['ID','SKU','Produto','Categoria','Estoque','Mínimo','Situação','Preço Venda','Atualizado'], d.produto_id,
      [d.produto_id,d.sku,d.produto,d.categoria,d.estoque_atual,d.estoque_minimo,situacaoEstoque(d.estoque_atual,d.estoque_minimo),d.preco_venda,sync]);
    return;
  }

  if (p.entidade_tipo === 'PEDIDO') {
    upsert(ss, 'Vendas', ['ID','Código','Cliente','CPF/CNPJ','Telefone','E-mail','Status','Subtotal','Desconto','Total','Reservado','Expira em','Data','Atualizado'], d.id,
      [d.id,d.codigo,d.cliente_nome,d.cpf_cnpj,d.telefone,d.email,d.status,d.subtotal,d.desconto,d.total,d.estoque_reservado,d.reserva_expira_em,d.criado_em,sync]);
    return;
  }

  if (p.entidade_tipo === 'PAGAMENTO') {
    upsert(ss, 'Pagamentos', ['ID','Pedido ID','Pedido','Forma','Gateway','Valor','Status','ID Externo','Pago em','Data','Atualizado'], d.id,
      [d.id,d.pedido_id,d.pedido_codigo,d.forma,d.gateway,d.valor,d.status,d.identificador_externo,d.pago_em,d.criado_em,sync]);
    upsert(ss, 'Financeiro', ['ID','Empresa','Origem','Referência','Valor','Status','Data','Atualizado'], 'PAG-'+d.id,
      ['PAG-'+d.id,p.empresa,'Pagamento',d.pedido_codigo || d.pedido_id,d.valor,d.status,d.pago_em || d.criado_em,sync]);
    return;
  }

  if (p.entidade_tipo === 'SERVICO') {
    upsert(ss, 'Serviços', ['ID','Protocolo','Cliente','Equipamento','Nº Série','Problema','Diagnóstico','Status','Prazo','Estimado','Final','Criado','Atualizado'], d.id,
      [d.id,d.protocolo,d.cliente_nome,d.equipamento,d.numero_serie,d.problema,d.diagnostico,d.status,d.prazo,d.valor_estimado,d.valor_final,d.criado_em,sync]);
    if (p.aba_historico) appendOnce(ss, 'Serviços Histórico', ['Fila','Data Sync','Serviço ID','Protocolo','Status','Prazo','Estimado','Final','Tipo Evento'], p.fila_id,
      [p.fila_id,sync,d.id,d.protocolo,d.status,d.prazo,d.valor_estimado,d.valor_final,p.tipo]);
    if (Number(d.valor_final || 0) > 0) upsert(ss, 'Financeiro', ['ID','Empresa','Origem','Referência','Valor','Status','Data','Atualizado'], 'SER-'+d.id,
      ['SER-'+d.id,p.empresa,'Serviço',d.protocolo,d.valor_final,d.status,d.finalizado_em || d.atualizado_em || d.criado_em,sync]);
    return;
  }

  if (p.entidade_tipo === 'ORCAMENTO') {
    upsert(ss, 'Orçamentos', ['ID','Serviço ID','Protocolo','Código','Descrição','Mão de Obra','Peças','Total','Status','Enviado','Respondido','Criado','Atualizado'], d.id,
      [d.id,d.servico_id,d.protocolo,d.codigo,d.descricao,d.valor_mao_obra,d.valor_pecas,d.valor_total,d.status,d.enviado_em,d.respondido_em,d.criado_em,sync]);
    return;
  }

  if (p.entidade_tipo === 'VIAGEM') {
    upsert(ss, 'Viagens', ['ID','Código','Cliente','Origem','Destino','Saída','Finalização','Passageiros','Tipo','Status','Valor','Observação','Criado','Atualizado'], d.id,
      [d.id,d.codigo,d.cliente_nome,d.origem,d.destino,d.data_saida,d.data_finalizacao,d.passageiros,d.tipo,d.status,d.valor,d.observacao,d.criado_em,sync]);
    if (p.aba_historico) appendOnce(ss, 'Viagens Histórico', ['Fila','Data Sync','Viagem ID','Código','Status','Saída','Finalização','Valor','Tipo Evento'], p.fila_id,
      [p.fila_id,sync,d.id,d.codigo,d.status,d.data_saida,d.data_finalizacao,d.valor,p.tipo]);
    if (d.status === 'FINALIZADA' && Number(d.valor || 0) > 0) upsert(ss, 'Financeiro', ['ID','Empresa','Origem','Referência','Valor','Status','Data','Atualizado'], 'VIA-'+d.id,
      ['VIA-'+d.id,p.empresa,'Viagem',d.codigo,d.valor,d.status,d.data_finalizacao || d.data_saida,sync]);
    return;
  }

  if (p.entidade_tipo === 'DOCUMENTO') {
    upsert(ss, 'Documentos', ['ID','Empresa','Tipo','Vínculo','Vínculo ID','Arquivo','MIME','Tamanho','Processamento','Data','Atualizado'], d.id,
      [d.id,p.empresa,d.tipo,d.entidade_tipo,d.entidade_id,d.nome_original,d.mime_type,d.tamanho_bytes,d.status_processamento,d.criado_em,sync]);
    return;
  }

  appendOnce(ss, 'Eventos Integração', ['Fila','Data','Empresa','Tipo','Entidade','ID','JSON'], p.fila_id,
    [p.fila_id,sync,p.empresa,p.tipo,p.entidade_tipo,p.entidade_id,JSON.stringify(d)]);
}

function prepararEstrutura(ss) {
  const abas = [
    'Dashboard Geral','Catálogo','Estoque Atual','Movimentações Estoque','Vendas','Pagamentos',
    'Serviços','Serviços Histórico','Orçamentos','Viagens','Viagens Histórico','Financeiro','Documentos',
    'Eventos Integração','_SYNC'
  ];
  abas.forEach(n => { if (!ss.getSheetByName(n)) ss.insertSheet(n); });
  const sync = ss.getSheetByName('_SYNC');
  if (sync.getLastRow() === 0) sync.appendRow(['Fila','Empresa','Tipo','Entidade','ID','Processado em']);
  sync.hideSheet();
}

function upsert(ss, nome, headers, key, row) {
  const sh = ss.getSheetByName(nome) || ss.insertSheet(nome);
  ensureHeaders(sh, headers);
  const last = sh.getLastRow();
  let target = 0;
  if (last >= 2) {
    const vals = sh.getRange(2,1,last-1,1).getValues();
    for (let i=0;i<vals.length;i++) if (String(vals[i][0]) === String(key)) { target=i+2; break; }
  }
  if (target) sh.getRange(target,1,1,row.length).setValues([row]);
  else sh.appendRow(row);
}

function appendOnce(ss, nome, headers, filaId, row) {
  const sh = ss.getSheetByName(nome) || ss.insertSheet(nome);
  ensureHeaders(sh, headers);
  const last = sh.getLastRow();
  if (last >= 2) {
    const vals = sh.getRange(2,1,last-1,1).getValues();
    if (vals.some(v => String(v[0]) === String(filaId))) return;
  }
  sh.appendRow(row);
}

function ensureHeaders(sh, headers) {
  if (sh.getLastRow() === 0) {
    sh.appendRow(headers);
    sh.getRange(1,1,1,headers.length).setFontWeight('bold').setFrozenRows(1);
  }
}

function filaJaProcessada(ss, filaId) {
  const sh = ss.getSheetByName('_SYNC');
  if (!sh || sh.getLastRow() < 2) return false;
  const vals = sh.getRange(2,1,sh.getLastRow()-1,1).getValues();
  return vals.some(v => String(v[0]) === String(filaId));
}

function registrarFila(ss, p) {
  const sh = ss.getSheetByName('_SYNC');
  sh.appendRow([p.fila_id,p.empresa,p.tipo,p.entidade_tipo,p.entidade_id,new Date()]);
}

function situacaoEstoque(atual, minimo) {
  atual = Number(atual || 0); minimo = Number(minimo || 0);
  if (atual <= 0) return 'SEM ESTOQUE';
  if (atual <= minimo) return 'ESTOQUE BAIXO';
  return 'NORMAL';
}

function atualizarDashboard(ss) {
  const sh = ss.getSheetByName('Dashboard Geral');
  sh.clearContents();
  const vendas = contarLinhas(ss,'Vendas');
  const servicos = contarLinhas(ss,'Serviços');
  const viagens = contarLinhas(ss,'Viagens');
  const estoqueBaixo = contarStatus(ss,'Estoque Atual',7,['ESTOQUE BAIXO','SEM ESTOQUE']);
  const receitaComercial = somarCondicional(ss,'Pagamentos',7,'APROVADO',6);
  const receitaServicos = somarStatusMultiplos(ss,'Serviços',8,['FINALIZADO','ENTREGUE'],11);
  const receitaViagens = somarCondicional(ss,'Viagens',10,'FINALIZADA',11);
  const total = receitaComercial + receitaServicos + receitaViagens;

  sh.getRange('A1:B10').setValues([
    ['GRUPO BCMR - GESTÃO INTEGRADA',''],
    ['Atualizado em',new Date()],
    ['Faturamento consolidado',total],
    ['Comercial Marques',receitaComercial],
    ['Manutenções Marques',receitaServicos],
    ['Transportes BCMR',receitaViagens],
    ['Pedidos registrados',vendas],
    ['Serviços registrados',servicos],
    ['Viagens registradas',viagens],
    ['Produtos com atenção no estoque',estoqueBaixo]
  ]);
  sh.getRange('A1:B1').setFontWeight('bold');
  sh.getRange('B3:B6').setNumberFormat('R$ #,##0.00');
  sh.autoResizeColumns(1,2);
}

function contarLinhas(ss,nome) {
  const sh=ss.getSheetByName(nome); return sh ? Math.max(0,sh.getLastRow()-1) : 0;
}
function contarStatus(ss,nome,col,valores) {
  const sh=ss.getSheetByName(nome); if(!sh||sh.getLastRow()<2)return 0;
  return sh.getRange(2,col,sh.getLastRow()-1,1).getValues().filter(r=>valores.includes(String(r[0]))).length;
}
function somarCondicional(ss,nome,colStatus,status,colValor) {
  const sh=ss.getSheetByName(nome); if(!sh||sh.getLastRow()<2)return 0;
  const v=sh.getRange(2,1,sh.getLastRow()-1,Math.max(colStatus,colValor)).getValues();
  return v.reduce((s,r)=>String(r[colStatus-1])===status?s+Number(r[colValor-1]||0):s,0);
}
function somarStatusMultiplos(ss,nome,colStatus,statuses,colValor) {
  const sh=ss.getSheetByName(nome); if(!sh||sh.getLastRow()<2)return 0;
  const v=sh.getRange(2,1,sh.getLastRow()-1,Math.max(colStatus,colValor)).getValues();
  return v.reduce((s,r)=>statuses.includes(String(r[colStatus-1]))?s+Number(r[colValor-1]||0):s,0);
}
function json(o){return ContentService.createTextOutput(JSON.stringify(o)).setMimeType(ContentService.MimeType.JSON);}
