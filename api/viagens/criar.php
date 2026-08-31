<?php
require_once '../config/database.php'; require_once '../config/auth.php'; require_once '../eventos/helpers.php';
$u=requireAuth(); requireCsrf(); if(!usuarioTemAcessoEmpresa($conn,$u['id'],3,$u['perfil'])) jsonResponse(false,'Sem acesso ao Grupo BCMR Transportes.',[],403);
$in=readJson(); $cliente=trim($in['cliente']??''); $origem=trim($in['origem']??''); $destino=trim($in['destino']??''); $data=trim($in['data_saida']??'');
$passageiros=max(1,(int)($in['passageiros']??1)); $tipo=trim($in['tipo']??''); $obs=trim($in['observacao']??''); $valor=(float)($in['valor']??0);
if($origem===''||$destino===''||$data==='') jsonResponse(false,'Informe origem, destino e data/hora.',[],400);
$data=str_replace('T',' ',$data).(strlen($data)===16?':00':'');
$conn->begin_transaction();
try{
    $codigo='BCMR-'.date('YmdHis').'-'.random_int(100,999);
    $s=$conn->prepare("INSERT INTO viagens(empresa_id,codigo,cliente_nome,origem,destino,data_saida,passageiros,tipo,observacao,status,valor) VALUES(3,?,?,?,?,?,?,?,?, 'AGENDADA',?)");
    $s->bind_param('sssssissd',$codigo,$cliente,$origem,$destino,$data,$passageiros,$tipo,$obs,$valor);$s->execute();$id=(int)$conn->insert_id;
    $h=$conn->prepare("INSERT INTO viagens_historico(viagem_id,usuario_id,status_anterior,status_novo,observacao) VALUES(?,?,'','AGENDADA',?)");$hobs='Viagem criada no painel administrativo';$h->bind_param('iis',$id,$u['id'],$hobs);$h->execute();
    $desc=$codigo.' · '.$origem.' → '.$destino;
    criarEventoENotificar($conn,3,$u['id'],'Nova viagem agendada',$desc,'INFORMATIVO','VIAGEM',$id,null,'TRANSPORTE','NOVA_VIAGEM');
    registrarAuditoria($conn,$u['id'],3,'CRIAR_VIAGEM','VIAGEM',$id,$desc); enfileirarSheets($conn,3,'NOVA_VIAGEM','VIAGEM',$id);
    $conn->commit();jsonResponse(true,'Viagem cadastrada.',['viagem_id'=>$id,'codigo'=>$codigo],201);
}catch(Throwable $e){$conn->rollback();jsonResponse(false,'Erro ao cadastrar viagem: '.$e->getMessage(),[],400);}
