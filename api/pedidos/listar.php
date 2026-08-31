<?php
require_once '../config/database.php';require_once '../config/auth.php';$u=requireAuth();if(!usuarioTemAcessoEmpresa($conn,$u['id'],1,$u['perfil']))jsonResponse(false,'Sem acesso.',[],403);
$q=$conn->query("SELECT p.id,p.codigo,p.status,p.total,p.estoque_reservado,p.reserva_expira_em,p.criado_em,c.nome cliente,(SELECT COUNT(*) FROM pedido_itens pi WHERE pi.pedido_id=p.id) itens FROM pedidos p LEFT JOIN clientes c ON c.id=p.cliente_id WHERE p.empresa_id=1 ORDER BY p.id DESC LIMIT 100");jsonResponse(true,'', ['pedidos'=>$q->fetch_all(MYSQLI_ASSOC)]);
