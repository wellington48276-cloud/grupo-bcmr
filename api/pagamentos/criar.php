<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../eventos/helpers.php';
$u=requireAuth(); requireCsrf();
if(!usuarioTemAcessoEmpresa($conn,$u['id'],1,$u['perfil'])) jsonResponse(false,'Sem acesso à Comercial Marques.',[],403);
$in=readJson(); $pedidoId=(int)($in['pedido_id']??0); $forma=strtoupper(trim($in['forma']??'PIX')); $gateway=substr(strtoupper(trim($in['gateway']??'MANUAL')),0,80); $valor=(float)($in['valor']??0);
$formas=['PIX','CARTAO','DINHEIRO','LINK_PAGAMENTO','OUTRO']; if(!in_array($forma,$formas,true)||$valor<=0) jsonResponse(false,'Forma ou valor inválido.',[],400);
if($pedidoId){$s=$conn->prepare('SELECT id,total FROM pedidos WHERE id=? AND empresa_id=1');$s->bind_param('i',$pedidoId);$s->execute();if(!$s->get_result()->fetch_assoc())jsonResponse(false,'Pedido não encontrado.',[],404);}
$key=bin2hex(random_bytes(16));
$s=$conn->prepare("INSERT INTO pagamentos(empresa_id,pedido_id,forma,gateway,valor,status,idempotency_key) VALUES(1,NULLIF(?,0),?,?,?,'PENDENTE',?)");$s->bind_param('issds',$pedidoId,$forma,$gateway,$valor,$key);$s->execute();$id=(int)$conn->insert_id;
criarEventoENotificar($conn,1,$u['id'],'Pagamento criado','Pagamento #'.$id.' aguardando confirmação.','INFORMATIVO','PAGAMENTO',$id,null,'PAGAMENTOS','PAGAMENTO_CRIADO');
registrarAuditoria($conn,$u['id'],1,'CRIAR_PAGAMENTO','PAGAMENTO',$id,'Valor R$ '.number_format($valor,2,',','.').' · '.$forma.' · '.$gateway);
jsonResponse(true,'Pagamento criado.',['pagamento_id'=>$id,'idempotency_key'=>$key],201);
