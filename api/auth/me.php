<?php
require_once '../config/database.php'; require_once '../config/auth.php';
$u=requireAuth();
$emp=[];
if ($u['perfil']==='ADMIN_GERAL') {
  $r=$conn->query('SELECT id,nome,tipo FROM empresas WHERE ativo=1 ORDER BY id');
} else {
  $stmt=$conn->prepare('SELECT e.id,e.nome,e.tipo FROM empresas e JOIN usuario_empresas ue ON ue.empresa_id=e.id WHERE ue.usuario_id=? AND e.ativo=1 ORDER BY e.id');
  $stmt->bind_param('i',$u['id']); $stmt->execute(); $r=$stmt->get_result();
}
while($x=$r->fetch_assoc()) $emp[]=$x;
jsonResponse(true,'',['usuario'=>$u,'empresas'=>$emp,'csrf'=>csrfToken()]);
