<?php
require_once '../config/database.php'; require_once '../config/auth.php'; $u=requireAuth();
if(!usuarioTemAcessoEmpresa($conn,$u['id'],1,$u['perfil'])) jsonResponse(false,'Sem acesso.',[],403);
$produto=(int)($_GET['produto_id']??0);
$sql="SELECT m.id,m.tipo,m.quantidade,m.estoque_anterior,m.estoque_posterior,m.referencia,m.observacao,m.criado_em,p.nome produto,p.sku,u.nome usuario FROM estoque_movimentacoes m JOIN produtos p ON p.id=m.produto_id LEFT JOIN usuarios u ON u.id=m.usuario_id WHERE m.empresa_id=1";
if($produto>0){$sql.=' AND m.produto_id=? ORDER BY m.criado_em DESC LIMIT 100';$s=$conn->prepare($sql);$s->bind_param('i',$produto);$s->execute();$r=$s->get_result();}
else{$r=$conn->query($sql.' ORDER BY m.criado_em DESC LIMIT 100');}
$d=[];while($x=$r->fetch_assoc())$d[]=$x;jsonResponse(true,'',['movimentacoes'=>$d]);
