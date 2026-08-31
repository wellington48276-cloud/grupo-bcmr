<?php
require_once '../config/database.php'; require_once '../config/auth.php'; $u=requireAuth();
if($u['perfil']!=='ADMIN_GERAL') jsonResponse(false,'A auditoria é restrita ao administrador geral.',[],403);
$r=$conn->query("SELECT a.id,a.criado_em,u.nome usuario,e.nome empresa,a.acao,a.entidade_tipo,a.entidade_id,a.descricao,a.ip FROM auditoria a LEFT JOIN usuarios u ON u.id=a.usuario_id LEFT JOIN empresas e ON e.id=a.empresa_id ORDER BY a.criado_em DESC LIMIT 200");$d=[];while($x=$r->fetch_assoc())$d[]=$x;jsonResponse(true,'',['auditoria'=>$d]);
