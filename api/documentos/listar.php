<?php
require_once '../config/database.php';require_once '../config/auth.php';
$u=requireAuth();
$sql="SELECT d.id,d.empresa_id,e.nome empresa,d.tipo,d.entidade_tipo,d.entidade_id,d.nome_original,d.mime_type,d.tamanho_bytes,d.status_processamento,d.criado_em FROM documentos d JOIN empresas e ON e.id=d.empresa_id WHERE (d.empresa_id IN (SELECT empresa_id FROM usuario_empresas WHERE usuario_id=?) OR ?='ADMIN_GERAL') ORDER BY d.id DESC LIMIT 100";
$s=$conn->prepare($sql);$s->bind_param('is',$u['id'],$u['perfil']);$s->execute();jsonResponse(true,'OK',['documentos'=>$s->get_result()->fetch_all(MYSQLI_ASSOC)]);
