<?php
require_once '../config/database.php';
require_once '../config/auth.php';
$u = requireAuth();
if ($u['perfil'] !== 'ADMIN_GERAL') jsonResponse(false,'Acesso restrito ao administrador geral.',[],403);

$totais = ['PENDENTE'=>0,'PROCESSANDO'=>0,'SINCRONIZADO'=>0,'ERRO'=>0];
$r = $conn->query("SELECT status,COUNT(*) total FROM integracao_sheets GROUP BY status");
while ($row=$r->fetch_assoc()) $totais[$row['status']] = (int)$row['total'];

$ultimos=[];
$r=$conn->query("SELECT i.id,i.empresa_id,e.nome empresa,i.tipo,i.entidade_tipo,i.entidade_id,i.status,i.tentativa,i.erro,i.criado_em,i.processado_em FROM integracao_sheets i LEFT JOIN empresas e ON e.id=i.empresa_id ORDER BY i.id DESC LIMIT 30");
while($row=$r->fetch_assoc()) $ultimos[]=$row;

jsonResponse(true,'Status da integração.',['configurado'=>(getenv('BCMR_SHEETS_WEBHOOK')?:'')!=='','totais'=>$totais,'ultimos'=>$ultimos]);
