<?php
require_once '../config/database.php'; require_once '../config/auth.php'; $u=requireAuth(); if(!usuarioTemAcessoEmpresa($conn,$u['id'],3,$u['perfil'])) jsonResponse(false,'Sem acesso.',[],403);
$r=$conn->query("SELECT id,codigo,cliente_nome,origem,destino,data_saida,data_finalizacao,passageiros,tipo,observacao,status,valor,TIMESTAMPDIFF(MINUTE,data_saida,NOW()) minutos_atraso,TIMESTAMPDIFF(MINUTE,NOW(),data_saida) minutos_para_saida FROM viagens WHERE empresa_id=3 ORDER BY data_saida DESC LIMIT 100");$d=[];while($x=$r->fetch_assoc())$d[]=$x;jsonResponse(true,'',['viagens'=>$d]);
