<?php
require_once '../config/database.php'; require_once '../config/auth.php';
$u=requireAuth(); if(!usuarioTemAcessoEmpresa($conn,$u['id'],1,$u['perfil'])) jsonResponse(false,'Sem acesso.',[],403);
$q=trim($_GET['q']??''); $like='%'.$q.'%';
$stmt=$conn->prepare("SELECT p.id,p.sku,p.nome,p.marca,p.modelo,p.preco_venda,p.estoque_atual,p.estoque_minimo,c.nome categoria,CASE WHEN p.estoque_atual=0 THEN 'SEM_ESTOQUE' WHEN p.estoque_atual<=p.estoque_minimo THEN 'BAIXO' ELSE 'NORMAL' END situacao FROM produtos p LEFT JOIN categorias c ON c.id=p.categoria_id WHERE p.empresa_id=1 AND p.ativo=1 AND (p.nome LIKE ? OR p.sku LIKE ? OR COALESCE(p.marca,'') LIKE ?) ORDER BY p.nome");
$stmt->bind_param('sss',$like,$like,$like); $stmt->execute(); $r=$stmt->get_result(); $dados=[]; while($x=$r->fetch_assoc())$dados[]=$x; jsonResponse(true,'',['produtos'=>$dados]);
