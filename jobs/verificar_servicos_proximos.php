<?php
require_once __DIR__.'/_bootstrap.php';
$r=$conn->query("SELECT id,protocolo,equipamento FROM servicos WHERE empresa_id=2 AND prazo>NOW() AND prazo<=DATE_ADD(NOW(),INTERVAL 24 HOUR) AND status NOT IN ('FINALIZADO','ENTREGUE','CANCELADO')");$total=0;
while($s=$r->fetch_assoc()){$id=(int)$s['id'];$key='SERVICO_PRAZO_PROXIMO:'.$id;if(($q=$conn->prepare('SELECT id FROM eventos WHERE chave_unica=?'))){$q->bind_param('s',$key);$q->execute();if($q->get_result()->fetch_assoc())continue;}criarEventoENotificar($conn,2,0,'Prazo de serviço próximo',$s['protocolo'].' · prazo em menos de 24 horas.','ATENCAO','SERVICO',$id,$key,'MANUTENCAO','SERVICO_PRAZO_PROXIMO');$total++;}echo json_encode(['status'=>true,'novos_alertas'=>$total],JSON_UNESCAPED_UNICODE);
