const views=[...document.querySelectorAll('.view')];
const navButtons=[...document.querySelectorAll('[data-view]')];
const titles={dashboard:'Dashboard Geral',comercial:'Comercial Marques',manutencoes:'Manutenções Marques',transportes:'Transportes BCMR',notificacoes:'Notificações',pagamentos:'Pagamentos',documentos:'Documentos',auditoria:'Auditoria',configuracoes:'Configurações'};
let csrf='';
let modalMode='';
let modalContext={};
const money=v=>new Intl.NumberFormat('pt-BR',{style:'currency',currency:'BRL'}).format(Number(v)||0);
const esc=v=>String(v??'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c]));
const statusLabel=s=>String(s||'').replaceAll('_',' ');

async function api(url,opt={}){
  const headers={...(opt.headers||{})};
  if(opt.method&&opt.method!=='GET'&&opt.method!=='HEAD'){
    headers['Content-Type']='application/json';
    headers['X-CSRF-Token']=csrf;
  }
  const r=await fetch(url,{credentials:'same-origin',...opt,headers});
  if(r.status===401){location.href='login.php';throw new Error('Sessão expirada.');}
  let j;try{j=await r.json();}catch{throw new Error('Resposta inválida do servidor.');}
  if(!j.status)throw new Error(j.mensagem||'Erro na API');
  return j.dados;
}

function showView(id){
  views.forEach(v=>v.classList.toggle('active-view',v.id===id));
  document.getElementById('pageTitle').textContent=titles[id]||'Grupo BCMR';
  navButtons.forEach(b=>b.classList.toggle('active',b.dataset.view===id));
  if(id==='comercial')loadProducts();
  if(id==='manutencoes')loadServices();
  if(id==='transportes')loadTrips();
  if(id==='notificacoes')loadNotifications();
  if(id==='pagamentos')loadPayments();
  if(id==='documentos')loadDocuments();
  if(id==='auditoria')loadAudit();
}
navButtons.forEach(b=>b.addEventListener('click',()=>showView(b.dataset.view)));
document.querySelectorAll('.company-card').forEach(c=>c.addEventListener('click',()=>showView(c.dataset.open)));

async function loadDashboard(){
  try{
    const d=await api('api/dashboard/geral.php');
    document.getElementById('kpiRevenue').textContent=money(d.grupo.faturamento_total);
    document.getElementById('kpiSales').textContent=Number(d.comercial_marques.vendas||0);
    document.getElementById('kpiServices').textContent=Number(d.manutencoes_marques.abertos||0);
    document.getElementById('kpiTrips').textContent=Number(d.transportes_bcmr.viagens_hoje||0);
    const cards=document.querySelectorAll('.company-card');
    if(cards[0])cards[0].querySelectorAll('b').forEach((b,i)=>b.textContent=[`${Number(d.comercial_marques.itens_estoque||0)} itens`,Number(d.comercial_marques.vendas||0),Number(d.comercial_marques.estoque_baixo||0)][i]);
    if(cards[1])cards[1].querySelectorAll('b').forEach((b,i)=>b.textContent=[Number(d.manutencoes_marques.abertos||0),Number(d.manutencoes_marques.orcamentos||0),Number(d.manutencoes_marques.atrasados||0)][i]);
    if(cards[2])cards[2].querySelectorAll('b').forEach((b,i)=>b.textContent=[Number(d.transportes_bcmr.viagens_hoje||0),Number(d.transportes_bcmr.em_andamento||0),Number(d.transportes_bcmr.atrasadas||0)][i]);
    const feed=document.getElementById('activityFeed');feed.innerHTML='';
    (d.atividades||[]).slice(0,8).forEach(x=>{
      const el=document.createElement('button');el.type='button';el.className='feed-item notification-button';
      el.innerHTML=`<b>${esc(x.empresa)}</b><span>${esc(x.titulo)} · ${esc(x.descricao||'')}</span>`;
      el.addEventListener('click',()=>openEntity(x.entidade_tipo,x.entidade_id));feed.appendChild(el);
    });
    if(!feed.children.length)feed.innerHTML='<div class="feed-item"><span>Sem atividades registradas ainda.</span></div>';
    const n=d.grupo.notificacoes||{};
    document.querySelector('.priority.critical b').textContent=(Number(n.CRITICO)||0)+' críticas';
    document.querySelector('.priority.warning b').textContent=(Number(n.ATENCAO)||0)+' atenções';
    document.querySelector('.priority.info b').textContent=(Number(n.INFORMATIVO)||0)+' informativas';
  }catch(e){console.error(e);}
}

async function loadProducts(q=''){
  try{
    const d=await api('api/produtos/listar.php?q='+encodeURIComponent(q));
    const body=document.getElementById('productRows');body.innerHTML='';
    (d.produtos||[]).forEach(p=>{
      const s=p.situacao==='SEM_ESTOQUE'?['danger','Sem estoque']:p.situacao==='BAIXO'?['warn','Baixo']:['ok','Normal'];
      const tr=document.createElement('tr');
      tr.innerHTML=`<td>${esc(p.nome)}</td><td>${esc(p.sku)}</td><td>${esc(p.categoria||'-')}</td><td>${esc(p.estoque_atual)}</td><td>${esc(p.estoque_minimo)}</td><td><span class="pill ${s[0]}">${s[1]}</span></td><td><button class="btn small stock-btn">Movimentar</button></td>`;
      tr.querySelector('.stock-btn').addEventListener('click',()=>openStockModal(p));body.appendChild(tr);
    });
    if(!body.children.length)body.innerHTML='<tr><td colspan="7">Nenhum produto encontrado.</td></tr>';
  }catch(e){console.error(e);}
}

async function loadServices(){
  try{
    const d=await api('api/servicos/listar.php');const box=document.querySelector('#manutencoes .feed');box.innerHTML='';
    (d.servicos||[]).forEach(s=>{
      const el=document.createElement('div');el.className='feed-item';
      el.innerHTML=`<b>#${esc(s.protocolo)} · ${esc(s.equipamento)}</b><span class="${Number(s.atrasado)?'danger-text':''}">${Number(s.atrasado)?'Atrasado · ':''}${esc(statusLabel(s.status))}</span><button class="btn small service-status">Alterar status</button>`;
      el.querySelector('.service-status').addEventListener('click',()=>openServiceStatus(s));box.appendChild(el);
    });
    if(!box.children.length)box.innerHTML='<div class="feed-item"><span>Nenhum serviço cadastrado.</span></div>';
  }catch(e){console.error(e);}
}

async function loadTrips(){
  try{
    const d=await api('api/viagens/listar.php');const body=document.querySelector('#transportes tbody');body.innerHTML='';
    (d.viagens||[]).forEach(v=>{
      let label=statusLabel(v.status),cls='ok';
      if(v.status==='AGENDADA'&&Number(v.minutos_atraso)>0){label='Atrasada '+v.minutos_atraso+' min';cls='danger'}
      else if(v.status==='AGENDADA'&&Number(v.minutos_para_saida)>=0&&Number(v.minutos_para_saida)<=60){label='Em '+v.minutos_para_saida+' min';cls='warn'}
      const dt=new Date(String(v.data_saida).replace(' ','T'));const hora=Number.isNaN(dt.getTime())?v.data_saida:dt.toLocaleString('pt-BR',{day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'});
      const tr=document.createElement('tr');
      tr.innerHTML=`<td>#${esc(v.codigo)}</td><td>${esc(v.origem)} → ${esc(v.destino)}</td><td>${esc(hora)}</td><td><span class="pill ${cls}">${esc(label)}</span></td><td><button class="btn small trip-status">Alterar status</button></td>`;
      tr.querySelector('.trip-status').addEventListener('click',()=>openTripStatus(v));body.appendChild(tr);
    });
    if(!body.children.length)body.innerHTML='<tr><td colspan="5">Nenhuma viagem cadastrada.</td></tr>';
  }catch(e){console.error(e);}
}

async function loadNotifications(){
  try{
    const d=await api('api/notificacoes/listar.php');const list=d.notificacoes||[];const box=document.getElementById('notificationList');box.innerHTML='';let unread=0;
    list.forEach(n=>{
      if(!Number(n.lida))unread++;
      const el=document.createElement('button');el.type='button';el.className='feed-item notification-button';
      el.innerHTML=`<b>${esc(n.empresa)} · ${esc(n.prioridade)}</b><span>${esc(n.titulo)}</span><small>${esc(n.mensagem||'')}</small>`;
      el.addEventListener('click',async()=>{
        if(!Number(n.lida))await api('api/notificacoes/marcar_lida.php',{method:'POST',body:JSON.stringify({id:n.id})});
        openEntity(n.entidade_tipo,n.entidade_id);await Promise.all([loadNotifications(),loadDashboard()]);
      });box.appendChild(el);
    });
    if(!box.children.length)box.innerHTML='<div class="feed-item"><span>Sem notificações.</span></div>';
    document.getElementById('navBadge').textContent=unread;document.getElementById('mobileBadge').textContent=unread;
  }catch(e){console.error(e);}
}


async function apiForm(url,formData){
  const r=await fetch(url,{method:'POST',credentials:'same-origin',headers:{'X-CSRF-Token':csrf},body:formData});
  if(r.status===401){location.href='login.php';throw new Error('Sessão expirada.');}
  let j;try{j=await r.json();}catch{throw new Error('Resposta inválida do servidor.');}
  if(!j.status)throw new Error(j.mensagem||'Erro na API');return j.dados;
}

async function loadPayments(){
  const body=document.getElementById('paymentRows');if(!body)return;
  try{const d=await api('api/pagamentos/listar.php');body.innerHTML='';(d.pagamentos||[]).forEach(p=>{const tr=document.createElement('tr');const cls=p.status==='APROVADO'?'ok':(p.status==='PENDENTE'?'warn':'danger');tr.innerHTML=`<td>${esc(p.pedido_codigo||'-')}</td><td>${esc(statusLabel(p.forma))}</td><td>${esc(p.gateway)}</td><td>${money(p.valor)}</td><td><span class="pill ${cls}">${esc(statusLabel(p.status))}</span></td><td>${esc(p.criado_em)}</td><td><button class="btn small pay-status">Alterar</button></td>`;tr.querySelector('.pay-status').addEventListener('click',()=>openPaymentStatus(p));body.appendChild(tr)});if(!body.children.length)body.innerHTML='<tr><td colspan="7">Nenhum pagamento registrado.</td></tr>';}catch(e){body.innerHTML=`<tr><td colspan="7">${esc(e.message)}</td></tr>`;}
}

async function loadDocuments(){
  const body=document.getElementById('documentRows');if(!body)return;
  try{const d=await api('api/documentos/listar.php');body.innerHTML='';(d.documentos||[]).forEach(x=>{const tr=document.createElement('tr');tr.innerHTML=`<td><b>${esc(x.nome_original)}</b><br><small>${esc(x.mime_type||'')}</small></td><td>${esc(x.empresa)}</td><td>${esc(statusLabel(x.tipo))}</td><td>${esc(statusLabel(x.status_processamento))}</td><td><a class="btn small" href="api/documentos/download.php?id=${Number(x.id)}">Baixar</a> <button class="btn small process-doc">Processar</button></td>`;tr.querySelector('.process-doc').addEventListener('click',async()=>{try{await api('api/documentos/processar.php',{method:'POST',body:JSON.stringify({documento_id:Number(x.id)})});await loadDocuments();}catch(e){alert(e.message)}});body.appendChild(tr)});if(!body.children.length)body.innerHTML='<tr><td colspan="5">Nenhum documento enviado.</td></tr>';}catch(e){body.innerHTML=`<tr><td colspan="5">${esc(e.message)}</td></tr>`;}
}

async function loadAudit(){
  const body=document.querySelector('#auditoria tbody');if(!body)return;
  try{
    const d=await api('api/auditoria/listar.php');body.innerHTML='';
    (d.auditoria||[]).forEach(a=>{const tr=document.createElement('tr');tr.innerHTML=`<td>${esc(a.criado_em)}</td><td>${esc(a.usuario||'-')}</td><td>${esc(a.empresa||'-')}</td><td><b>${esc(a.acao)}</b><br><small>${esc(a.descricao||'')}</small></td>`;body.appendChild(tr)});
    if(!body.children.length)body.innerHTML='<tr><td colspan="4">Nenhum registro de auditoria.</td></tr>';
  }catch(e){body.innerHTML=`<tr><td colspan="4">${esc(e.message)}</td></tr>`;}
}

function openEntity(tipo,id){
  if(!tipo||!id)return;
  if(tipo==='PRODUTO'){showView('comercial');setTimeout(()=>document.querySelector(`button.stock-btn`)?.focus(),100);}
  if(tipo==='SERVICO')showView('manutencoes');
  if(tipo==='VIAGEM')showView('transportes');
  if(tipo==='PAGAMENTO')showView('pagamentos');
  if(tipo==='DOCUMENTO')showView('documentos');
}

const entityModal=document.getElementById('entityModal');
const entityForm=document.getElementById('entityForm');
const modalFields=document.getElementById('modalFields');
const modalTitle=document.getElementById('modalTitle');
const modalEyebrow=document.getElementById('modalEyebrow');
const modalMessage=document.getElementById('modalMessage');
function field(label,name,type='text',extra=''){return `<label>${label}<input name="${name}" type="${type}" ${extra}></label>`;}
function selectField(label,name,options){return `<label>${label}<select name="${name}">${options.map(x=>`<option value="${esc(x[0])}">${esc(x[1])}</option>`).join('')}</select></label>`;}
function textareaField(label,name,placeholder=''){return `<label style="grid-column:1/-1">${label}<textarea name="${name}" placeholder="${esc(placeholder)}"></textarea></label>`;}
function openModal(){modalMessage.textContent='';modalMessage.className='modal-message';entityModal.showModal();}

function openEntityModal(mode){
  modalMode=mode;modalContext={};
  if(mode==='produto'){
    modalEyebrow.textContent='Empresa 1 · Comercial Marques';modalTitle.textContent='Novo produto';
    modalFields.innerHTML=field('Nome do produto','nome','text','required')+field('SKU','sku','text','required')+selectField('Categoria','categoria_id',[[1,'Celulares'],[2,'Notebooks'],[3,'Videogames'],[4,'Computadores'],[5,'TVs'],[6,'Rádios'],[7,'Maquininhas'],[8,'Acessórios'],[0,'Sem categoria']])+field('Marca','marca')+field('Modelo','modelo')+field('Preço de compra','preco_compra','number','min="0" step="0.01" value="0"')+field('Preço de venda','preco_venda','number','min="0" step="0.01" value="0"')+field('Estoque inicial','estoque_inicial','number','min="0" value="0" required')+field('Estoque mínimo','estoque_minimo','number','min="0" value="1" required');
  }else if(mode==='servico'){
    modalEyebrow.textContent='Empresa 2 · Manutenções Marques';modalTitle.textContent='Novo serviço';
    const d=new Date(Date.now()+3*86400000).toISOString().slice(0,16);
    modalFields.innerHTML=field('Cliente','cliente','text','required')+field('Equipamento','equipamento','text','required')+field('Prazo','prazo','datetime-local',`value="${d}" required`)+textareaField('Problema relatado','problema');
  }else if(mode==='viagem'){
    modalEyebrow.textContent='Empresa 3 · Transportes BCMR';modalTitle.textContent='Nova viagem';
    const d=new Date(Date.now()+86400000).toISOString().slice(0,16);
    modalFields.innerHTML=field('Cliente','cliente')+field('Origem','origem','text','required')+field('Destino','destino','text','required')+field('Data e hora','data_saida','datetime-local',`value="${d}" required`)+field('Passageiros','passageiros','number','min="1" value="1" required')+field('Tipo','tipo','text','placeholder="Executivo, transfer..."')+field('Valor','valor','number','min="0" step="0.01" value="0"')+textareaField('Observação','observacao');
  }
  openModal();
}
function openStockModal(p){
  modalMode='estoque';modalContext={produto_id:Number(p.id)};modalEyebrow.textContent='Comercial Marques · Estoque';modalTitle.textContent=`Movimentar ${p.nome}`;
  modalFields.innerHTML=selectField('Tipo','tipo',[['ENTRADA','Entrada'],['SAIDA_MANUAL','Saída manual'],['DEVOLUCAO','Devolução'],['PERDA','Perda'],['AJUSTE','Ajustar estoque para']])+field('Quantidade','quantidade','number','min="0" value="1" required')+textareaField('Observação','observacao');openModal();
}
function openServiceStatus(s){
  modalMode='servico_status';modalContext={servico_id:Number(s.id)};modalEyebrow.textContent='Manutenções Marques';modalTitle.textContent=`${s.protocolo} · Alterar status`;
  const all=['RECEBIDO','EM_ANALISE','AGUARDANDO_ORCAMENTO','AGUARDANDO_APROVACAO','EM_MANUTENCAO','FINALIZADO','ENTREGUE','CANCELADO'];
  modalFields.innerHTML=selectField('Novo status','status',all.map(x=>[x,statusLabel(x)]))+textareaField('Observação','observacao');
  modalFields.querySelector('[name="status"]').value=s.status;openModal();
}
function openPaymentStatus(p){
  modalMode='pagamento_status';modalContext={pagamento_id:Number(p.id)};modalEyebrow.textContent='Comercial Marques · Pagamentos';modalTitle.textContent=`Pagamento #${p.id}`;
  modalFields.innerHTML=selectField('Novo status','status',[['APROVADO','Aprovado'],['RECUSADO','Recusado'],['CANCELADO','Cancelado'],['ESTORNADO','Estornado']]);openModal();
}
function openPaymentModal(){
  modalMode='pagamento';modalContext={};modalEyebrow.textContent='Comercial Marques · Pagamentos';modalTitle.textContent='Novo pagamento';
  modalFields.innerHTML=field('ID do pedido (opcional)','pedido_id','number','min="0" value="0"')+selectField('Forma','forma',[['PIX','PIX'],['CARTAO','Cartão'],['DINHEIRO','Dinheiro'],['LINK_PAGAMENTO','Link de pagamento'],['OUTRO','Outro']])+field('Gateway','gateway','text','value="MANUAL"')+field('Valor','valor','number','min="0.01" step="0.01" required');openModal();
}

function openTripStatus(v){
  modalMode='viagem_status';modalContext={viagem_id:Number(v.id)};modalEyebrow.textContent='Transportes BCMR';modalTitle.textContent=`${v.codigo} · Alterar status`;
  const all=['AGENDADA','A_CAMINHO','EMBARQUE','EM_VIAGEM','FINALIZADA','CANCELADA'];
  modalFields.innerHTML=selectField('Novo status','status',all.map(x=>[x,statusLabel(x)]))+textareaField('Observação','observacao');modalFields.querySelector('[name="status"]').value=v.status;openModal();
}

entityForm.addEventListener('submit',async e=>{
  e.preventDefault();const data=Object.fromEntries(new FormData(entityForm).entries());let url='';let refresh=async()=>{};
  if(modalMode==='produto'){url='api/produtos/criar.php';refresh=loadProducts;}
  if(modalMode==='servico'){url='api/servicos/criar.php';refresh=loadServices;}
  if(modalMode==='viagem'){url='api/viagens/criar.php';refresh=loadTrips;}
  if(modalMode==='estoque'){url='api/estoque/movimentar.php';Object.assign(data,modalContext);refresh=loadProducts;}
  if(modalMode==='servico_status'){url='api/servicos/atualizar_status.php';Object.assign(data,modalContext);refresh=loadServices;}
  if(modalMode==='viagem_status'){url='api/viagens/atualizar_status.php';Object.assign(data,modalContext);refresh=loadTrips;}
  if(modalMode==='pagamento'){url='api/pagamentos/criar.php';refresh=loadPayments;}
  if(modalMode==='pagamento_status'){url='api/pagamentos/atualizar_status.php';Object.assign(data,modalContext);refresh=loadPayments;}
  try{
    const result=await api(url,{method:'POST',body:JSON.stringify(data)});modalMessage.textContent='Salvo com sucesso.';modalMessage.className='modal-message success';
    await Promise.all([refresh(),loadDashboard(),loadNotifications()]);setTimeout(()=>entityModal.close(),450);return result;
  }catch(err){modalMessage.textContent=err.message;modalMessage.className='modal-message error';}
});

['closeModal','cancelModal'].forEach(id=>document.getElementById(id).addEventListener('click',()=>entityModal.close()));
document.getElementById('newProductBtn').addEventListener('click',()=>openEntityModal('produto'));
document.getElementById('newServiceBtn').addEventListener('click',()=>openEntityModal('servico'));
document.getElementById('newTripBtn').addEventListener('click',()=>openEntityModal('viagem'));
document.getElementById('newPaymentBtn').addEventListener('click',openPaymentModal);
document.getElementById('markAll').addEventListener('click',async()=>{try{await api('api/notificacoes/marcar_lida.php',{method:'POST',body:JSON.stringify({todas:true})});await Promise.all([loadNotifications(),loadDashboard()]);}catch(e){alert(e.message)}});
document.getElementById('refreshData').addEventListener('click',()=>Promise.all([loadDashboard(),loadProducts(),loadServices(),loadTrips(),loadNotifications()]));
document.getElementById('logoutBtn').addEventListener('click',async()=>{try{await api('api/auth/logout.php',{method:'POST',body:'{}'});}finally{location.href='login.php';}});
document.getElementById('companySelect').addEventListener('change',e=>{const map={grupo:'dashboard',comercial:'comercial',manutencoes:'manutencoes',transportes:'transportes'};showView(map[e.target.value]);});
document.getElementById('productSearch').addEventListener('input',e=>loadProducts(e.target.value));
const documentForm=document.getElementById('documentForm');if(documentForm)documentForm.addEventListener('submit',async e=>{e.preventDefault();const msg=document.getElementById('documentMessage');try{await apiForm('api/documentos/upload.php',new FormData(documentForm));msg.textContent='Documento enviado com segurança.';msg.className='modal-message success';documentForm.reset();await Promise.all([loadDocuments(),loadDashboard(),loadNotifications()]);}catch(err){msg.textContent=err.message;msg.className='modal-message error';}});

async function init(){
  try{const me=await api('api/auth/me.php');csrf=me.csrf;document.getElementById('userName').textContent=me.usuario.nome+' · '+me.usuario.perfil.replaceAll('_',' ');await Promise.all([loadDashboard(),loadNotifications()]);}
  catch(e){console.error(e);}
}
if('serviceWorker' in navigator)navigator.serviceWorker.register('service-worker.js').catch(()=>{});
init();
