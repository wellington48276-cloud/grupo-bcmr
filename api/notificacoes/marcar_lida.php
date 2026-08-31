<?php
require_once '../config/database.php'; require_once '../config/auth.php'; $u=requireAuth(); requireCsrf(); $in=readJson();
if(!empty($in['todas'])){$stmt=$conn->prepare('UPDATE notificacoes SET lida=1,lida_em=NOW() WHERE usuario_id=? AND lida=0');$stmt->bind_param('i',$u['id']);}
else{$id=(int)($in['id']??0);$stmt=$conn->prepare('UPDATE notificacoes SET lida=1,lida_em=NOW() WHERE id=? AND usuario_id=?');$stmt->bind_param('ii',$id,$u['id']);}
$stmt->execute();jsonResponse(true,'Atualizado.');
