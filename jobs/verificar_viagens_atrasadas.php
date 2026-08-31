<?php
require_once __DIR__.'/_bootstrap.php';
$r=$conn->query("SELECT id,codigo,origem,destino,TIMESTAMPDIFF(MINUTE,data_saida,NOW()) minutos FROM viagens WHERE empresa_id=3 AND status='AGENDADA' AND data_saida<NOW()");$total=0;
while($v=$r->fetch_assoc()){$id=(int)$v['id'];$key='VIAGEM_ATRASADA:'.$id;if(($q=$conn->prepare('SELECT id FROM eventos WHERE chave_unica=?'))){$q->bind_param('s',$key);$q->execute();if($q->get_result()->fetch_assoc())continue;}$desc=$v['codigo'].' · '.$v['origem'].' → '.$v['destino'].' · atrasada '.$v['minutos'].' min';criarEventoENotificar($conn,3,0,'Viagem atrasada',$desc,'CRITICO','VIAGEM',$id,$key,'TRANSPORTE','VIAGEM_ATRASADA');$total++;}echo json_encode(['status'=>true,'novos_alertas'=>$total],JSON_UNESCAPED_UNICODE);
