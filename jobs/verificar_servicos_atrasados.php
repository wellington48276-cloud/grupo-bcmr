<?php
require_once __DIR__.'/_bootstrap.php';
$r=$conn->query("SELECT id,protocolo,equipamento FROM servicos WHERE empresa_id=2 AND prazo<NOW() AND status NOT IN ('FINALIZADO','ENTREGUE','CANCELADO')");$total=0;
while($s=$r->fetch_assoc()){
    $id=(int)$s['id'];$key='SERVICO_ATRASADO:'.$id;
    $before=$conn->prepare('SELECT id FROM eventos WHERE chave_unica=?');$before->bind_param('s',$key);$before->execute();if($before->get_result()->fetch_assoc())continue;
    criarEventoENotificar($conn,2,0,'Serviço atrasado',$s['protocolo'].' · '.$s['equipamento'].' ultrapassou o prazo.','CRITICO','SERVICO',$id,$key,'MANUTENCAO','SERVICO_ATRASADO');$total++;
}
echo json_encode(['status'=>true,'novos_alertas'=>$total],JSON_UNESCAPED_UNICODE);
