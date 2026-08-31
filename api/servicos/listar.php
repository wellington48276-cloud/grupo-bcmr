<?php
require_once '../config/database.php'; require_once '../config/auth.php'; $u=requireAuth(); if(!usuarioTemAcessoEmpresa($conn,$u['id'],2,$u['perfil'])) jsonResponse(false,'Sem acesso.',[],403);
$r=$conn->query("SELECT id,protocolo,cliente_nome,equipamento,problema,status,prazo,valor_estimado,valor_final,CASE WHEN prazo<NOW() AND status NOT IN ('FINALIZADO','ENTREGUE','CANCELADO') THEN 1 ELSE 0 END atrasado FROM servicos WHERE empresa_id=2 ORDER BY criado_em DESC LIMIT 100");$d=[];while($x=$r->fetch_assoc())$d[]=$x;jsonResponse(true,'',['servicos'=>$d]);
