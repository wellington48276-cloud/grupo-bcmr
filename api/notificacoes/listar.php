<?php
require_once '../config/database.php'; require_once '../config/auth.php'; $u=requireAuth();
$stmt=$conn->prepare("SELECT n.id,n.empresa_id,e.nome empresa,n.titulo,n.mensagem,n.prioridade,n.lida,n.criado_em,ev.entidade_tipo,ev.entidade_id FROM notificacoes n JOIN empresas e ON e.id=n.empresa_id LEFT JOIN eventos ev ON ev.id=n.evento_id WHERE n.usuario_id=? ORDER BY n.criado_em DESC LIMIT 100");$stmt->bind_param('i',$u['id']);$stmt->execute();$r=$stmt->get_result();$d=[];while($x=$r->fetch_assoc())$d[]=$x;jsonResponse(true,'',['notificacoes'=>$d]);
