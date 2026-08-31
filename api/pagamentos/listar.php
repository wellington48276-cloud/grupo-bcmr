<?php
require_once '../config/database.php';
require_once '../config/auth.php';
$u=requireAuth();
if(!usuarioTemAcessoEmpresa($conn,$u['id'],1,$u['perfil'])) jsonResponse(false,'Sem acesso à Comercial Marques.',[],403);
$q=$conn->query("SELECT pg.id,pg.forma,pg.gateway,pg.valor,pg.status,pg.identificador_externo,pg.pago_em,pg.criado_em,p.codigo AS pedido_codigo FROM pagamentos pg LEFT JOIN pedidos p ON p.id=pg.pedido_id WHERE pg.empresa_id=1 ORDER BY pg.id DESC LIMIT 100");
jsonResponse(true,'OK',['pagamentos'=>$q->fetch_all(MYSQLI_ASSOC)]);
