<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['usuario_id'])) { header('Location: login.php'); exit; }
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="theme-color" content="#111827" />
  <title>Grupo BCMR Admin</title>
  <link rel="manifest" href="manifest.json" />
  <link rel="stylesheet" href="app.css" />
</head>
<body>
<div class="app-shell">
  <aside class="sidebar">
    <div class="brand">
      <div class="brand-mark">BCMR</div>
      <div><strong>Grupo BCMR</strong><span>Painel Administrativo</span></div>
    </div>
    <nav id="sideNav" class="nav-list">
      <button data-view="dashboard" class="nav-item active">Início</button>
      <button data-view="comercial" class="nav-item">Comercial Marques</button>
      <button data-view="manutencoes" class="nav-item">Manutenções Marques</button>
      <button data-view="transportes" class="nav-item">Transportes BCMR</button>
      <button data-view="notificacoes" class="nav-item">Notificações <span id="navBadge" class="badge">6</span></button>
      <button data-view="pagamentos" class="nav-item">Pagamentos</button>
      <a href="gestao.php" class="nav-item" style="text-decoration:none">Gestão avançada</a>
      <a href="public/index.php" target="_blank" class="nav-item" style="text-decoration:none">Site público</a>
      <button data-view="documentos" class="nav-item">Documentos</button>
      <button data-view="auditoria" class="nav-item">Auditoria</button>
      <button data-view="configuracoes" class="nav-item">Configurações</button>
    </nav>
  </aside>

  <main class="main">
    <header class="topbar">
      <div>
        <span class="eyebrow">Central integrada</span>
        <h1 id="pageTitle">Dashboard Geral</h1>
      </div>
      <div class="top-actions"><span id="userName" style="font-size:13px;color:var(--muted)"></span>
        <select id="companySelect" aria-label="Filtrar empresa">
          <option value="grupo">Grupo BCMR</option>
          <option value="comercial">Comercial Marques</option>
          <option value="manutencoes">Manutenções Marques</option>
          <option value="transportes">Transportes BCMR</option>
        </select>
        <button id="refreshData" class="btn">Atualizar</button><button id="logoutBtn" class="btn primary">Sair</button>
      </div>
    </header>

    <section id="dashboard" class="view active-view">
      <div class="kpis">
        <article class="card kpi"><span>Faturamento hoje</span><strong id="kpiRevenue">R$ 18.420,00</strong><small>Somando as 3 empresas</small></article>
        <article class="card kpi"><span>Vendas</span><strong id="kpiSales">7</strong><small>Comercial Marques</small></article>
        <article class="card kpi"><span>Serviços abertos</span><strong id="kpiServices">8</strong><small>Manutenções Marques</small></article>
        <article class="card kpi"><span>Viagens hoje</span><strong id="kpiTrips">4</strong><small>Transportes BCMR</small></article>
      </div>

      <div class="company-grid">
        <article class="card company-card" data-open="comercial">
          <span class="company-label">Empresa 1</span><h2>Comercial Marques</h2>
          <div class="metric-row"><span>Estoque</span><b>187 itens</b></div>
          <div class="metric-row"><span>Vendas hoje</span><b>7</b></div>
          <div class="metric-row"><span>Estoque baixo</span><b>4</b></div>
        </article>
        <article class="card company-card" data-open="manutencoes">
          <span class="company-label">Empresa 2</span><h2>Manutenções Marques</h2>
          <div class="metric-row"><span>Serviços abertos</span><b>8</b></div>
          <div class="metric-row"><span>Orçamentos</span><b>3</b></div>
          <div class="metric-row"><span>Atrasados</span><b>1</b></div>
        </article>
        <article class="card company-card" data-open="transportes">
          <span class="company-label">Empresa 3</span><h2>Transportes BCMR</h2>
          <div class="metric-row"><span>Viagens hoje</span><b>4</b></div>
          <div class="metric-row"><span>Em andamento</span><b>1</b></div>
          <div class="metric-row"><span>Atrasadas</span><b>1</b></div>
        </article>
      </div>

      <div class="two-col">
        <article class="card">
          <div class="section-head"><div><span class="eyebrow">Tempo real</span><h2>Atividade das 3 empresas</h2></div><span class="live-dot">● Ao vivo</span></div>
          <div id="activityFeed" class="feed">
            <div class="feed-item"><b>Comercial Marques</b><span>Nova venda · Galaxy A55 · R$ 1.999,00</span></div>
            <div class="feed-item"><b>Manutenções Marques</b><span>Protocolo #MM-001582 atualizado para Em análise</span></div>
            <div class="feed-item"><b>Transportes BCMR</b><span>Viagem #BCMR-2581 inicia em 30 minutos</span></div>
          </div>
        </article>
        <article class="card">
          <div class="section-head"><div><span class="eyebrow">Pendências</span><h2>Prioridades</h2></div></div>
          <div class="priority critical"><b>3 críticas</b><span>Estoque, serviço e viagem</span></div>
          <div class="priority warning"><b>7 atenções</b><span>Precisam acompanhamento</span></div>
          <div class="priority info"><b>12 informativas</b><span>Atividades recentes</span></div>
        </article>
      </div>

      <article class="card chart-card">
        <div class="section-head"><div><span class="eyebrow">Últimos 6 meses</span><h2>Faturamento consolidado</h2></div></div>
        <div class="bars" aria-label="Gráfico de faturamento">
          <div><i style="height:45%"></i><span>Mar</span></div><div><i style="height:56%"></i><span>Abr</span></div><div><i style="height:62%"></i><span>Mai</span></div><div><i style="height:70%"></i><span>Jun</span></div><div><i style="height:82%"></i><span>Jul</span></div><div><i style="height:94%"></i><span>Ago</span></div>
        </div>
      </article>
    </section>

    <section id="comercial" class="view">
      <div class="section-head"><div><span class="eyebrow">Empresa 1</span><h2>Comercial Marques</h2></div><button id="newProductBtn" class="btn primary">+ Novo produto</button></div>
      <div class="kpis"><article class="card kpi"><span>Estoque</span><strong>187</strong></article><article class="card kpi"><span>Vendas hoje</span><strong>7</strong></article><article class="card kpi"><span>Faturamento</span><strong>R$ 8.750</strong></article><article class="card kpi"><span>Alertas</span><strong>5</strong></article></div>
      <article class="card table-card"><div class="section-head"><h2>Produtos e estoque</h2><input id="productSearch" placeholder="Buscar produto ou SKU" /></div><div class="table-wrap"><table><thead><tr><th>Produto</th><th>SKU</th><th>Categoria</th><th>Estoque</th><th>Mínimo</th><th>Status</th><th>Ações</th></tr></thead><tbody id="productRows"><tr><td>Samsung Galaxy A55</td><td>CM-CEL-0001</td><td>Celulares</td><td>5</td><td>2</td><td><span class="pill ok">Normal</span></td></tr><tr><td>PlayStation 5</td><td>CM-GAME-0002</td><td>Videogames</td><td>2</td><td>2</td><td><span class="pill warn">Baixo</span></td></tr><tr><td>Notebook Dell Inspiron</td><td>CM-NOTE-0003</td><td>Notebooks</td><td>0</td><td>1</td><td><span class="pill danger">Sem estoque</span></td></tr></tbody></table></div></article>
    </section>

    <section id="manutencoes" class="view">
      <div class="section-head"><div><span class="eyebrow">Empresa 2</span><h2>Manutenções Marques</h2></div><button id="newServiceBtn" class="btn primary">+ Novo serviço</button></div>
      <div class="kpis"><article class="card kpi"><span>Abertos</span><strong>8</strong></article><article class="card kpi"><span>Finalizados</span><strong>14</strong></article><article class="card kpi"><span>Orçamentos</span><strong>3</strong></article><article class="card kpi"><span>Atrasados</span><strong>1</strong></article></div>
      <div class="two-col"><article class="card"><div class="section-head"><h2>Serviços recentes</h2></div><div class="feed"><div class="feed-item"><b>#MM-001582 · Notebook Dell</b><span class="danger-text">Atrasado · prazo excedido</span></div><div class="feed-item"><b>#MM-001583 · iPhone 14</b><span>Em análise</span></div><div class="feed-item"><b>#MM-001584 · PC Gamer</b><span>Aguardando aprovação</span></div></div></article><article class="card"><span class="eyebrow">Timeline</span><h2>#MM-001582</h2><ol class="timeline"><li class="done">Recebido</li><li class="done">Em análise</li><li class="done">Orçamento aprovado</li><li class="current">Em manutenção</li><li>Finalizado</li><li>Entregue</li></ol></article></div>
    </section>

    <section id="transportes" class="view">
      <div class="section-head"><div><span class="eyebrow">Empresa 3</span><h2>Transportes BCMR</h2></div><button id="newTripBtn" class="btn primary">+ Nova viagem</button></div>
      <div class="kpis"><article class="card kpi"><span>Hoje</span><strong>4</strong></article><article class="card kpi"><span>Em andamento</span><strong>1</strong></article><article class="card kpi"><span>Próximas</span><strong>2</strong></article><article class="card kpi"><span>Atrasadas</span><strong>1</strong></article></div>
      <article class="card table-card"><div class="section-head"><h2>Viagens</h2></div><div class="table-wrap"><table><thead><tr><th>Código</th><th>Rota</th><th>Data/Hora</th><th>Status</th><th>Ações</th></tr></thead><tbody><tr><td colspan="5">Carregando viagens...</td></tr></tbody></table></div></article>
    </section>

    <section id="notificacoes" class="view">
      <div class="section-head"><div><span class="eyebrow">Central única</span><h2>Notificações</h2></div><button id="markAll" class="btn">Marcar todas como lidas</button></div>
      <div id="notificationList" class="feed large-feed"></div>
    </section>

    <section id="pagamentos" class="view">
      <div class="section-head"><div><span class="eyebrow">Comercial Marques</span><h2>Pagamentos</h2></div><button id="newPaymentBtn" class="btn primary">+ Novo pagamento</button></div>
      <article class="card table-card"><div class="table-wrap"><table><thead><tr><th>Pedido</th><th>Forma</th><th>Gateway</th><th>Valor</th><th>Status</th><th>Data</th><th>Ação</th></tr></thead><tbody id="paymentRows"><tr><td colspan="7">Carregando pagamentos...</td></tr></tbody></table></div></article>
    </section>

    <section id="documentos" class="view">
      <div class="two-col">
        <article class="card"><span class="eyebrow">Central compartilhada</span><h2>Enviar documento</h2>
          <form id="documentForm"><div class="form-grid">
            <label>Empresa<select name="empresa_id"><option value="1">Comercial Marques</option><option value="2">Manutenções Marques</option><option value="3">Transportes BCMR</option></select></label>
            <label>Tipo<select name="tipo"><option value="NFE">NF-e</option><option value="XML_NFE">XML NF-e</option><option value="ORCAMENTO">Orçamento</option><option value="PROTOCOLO">Protocolo</option><option value="COMPROVANTE">Comprovante</option><option value="FOTO">Foto</option><option value="OUTRO">Outro</option></select></label>
            <label>Entidade<select name="entidade_tipo"><option value="">Sem vínculo</option><option value="PEDIDO">Pedido</option><option value="SERVICO">Serviço</option><option value="VIAGEM">Viagem</option><option value="PRODUTO">Produto</option></select></label>
            <label>ID da entidade<input name="entidade_id" type="number" min="0" value="0"></label>
            <label style="grid-column:1/-1">Arquivo<input name="arquivo" type="file" accept=".pdf,.xml,.jpg,.jpeg,.png,.webp,.txt" required></label>
          </div><button class="btn primary" type="submit">Enviar documento</button><div id="documentMessage" class="modal-message"></div></form>
        </article>
        <article class="card table-card"><div class="section-head"><div><span class="eyebrow">Arquivos seguros</span><h2>Últimos documentos</h2></div></div><div class="table-wrap"><table><thead><tr><th>Arquivo</th><th>Empresa</th><th>Tipo</th><th>Processamento</th><th>Ações</th></tr></thead><tbody id="documentRows"><tr><td colspan="5">Carregando documentos...</td></tr></tbody></table></div></article>
      </div>
    </section>

    <section id="auditoria" class="view"><article class="card table-card"><div class="section-head"><div><span class="eyebrow">Rastreabilidade</span><h2>Auditoria</h2></div></div><div class="table-wrap"><table><thead><tr><th>Data</th><th>Usuário</th><th>Empresa</th><th>Ação</th></tr></thead><tbody><tr><td colspan="4">Carregando auditoria...</td></tr></tbody></table></div></article></section>

    <section id="configuracoes" class="view"><article class="card"><span class="eyebrow">Preferências</span><h2>Notificações</h2><div class="settings"><label><input type="checkbox" checked /> Novas vendas</label><label><input type="checkbox" checked /> Pagamentos recebidos</label><label><input type="checkbox" checked /> Estoque baixo</label><label><input type="checkbox" checked /> Serviços atrasados</label><label><input type="checkbox" checked /> Viagens próximas</label><label><input type="checkbox" checked /> Viagens atrasadas</label></div></article></section>
  </main>

  <dialog id="entityModal" class="entity-modal">
    <form id="entityForm" class="modal-card">
      <div class="section-head"><div><span id="modalEyebrow" class="eyebrow">Cadastro</span><h2 id="modalTitle">Novo registro</h2></div><button type="button" id="closeModal" class="btn">Fechar</button></div>
      <div id="modalFields" class="form-grid"></div>
      <div id="modalMessage" class="modal-message" aria-live="polite"></div>
      <div class="modal-actions"><button type="button" id="cancelModal" class="btn">Cancelar</button><button type="submit" class="btn primary">Salvar</button></div>
    </form>
  </dialog>

  <nav class="bottom-nav">
    <button data-view="dashboard" class="active">Início</button><button data-view="comercial">Gestão</button><button data-view="notificacoes">Alertas <span id="mobileBadge" class="badge">6</span></button><button data-view="configuracoes">Mais</button>
  </nav>
</div>
<script>window.BCMR_USER = <?= json_encode(["id"=>(int)$_SESSION["usuario_id"],"nome"=>$_SESSION["usuario_nome"]??"","perfil"=>$_SESSION["perfil"]??"FUNCIONARIO"], JSON_UNESCAPED_UNICODE) ?>;</script><script src="app.js"></script>
</body>
</html>
