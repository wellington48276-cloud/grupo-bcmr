<?php
require_once '../config/database.php'; require_once '../config/auth.php'; require_once '../eventos/helpers.php';
$u=requireAuth(); requireCsrf(); if(!usuarioTemAcessoEmpresa($conn,$u['id'],3,$u['perfil'])) jsonResponse(false,'Sem acesso ao Grupo BCMR Transportes.',[],403);
$in=readJson();$id=(int)($in['viagem_id']??0);$novo=strtoupper(trim($in['status']??''));$obs=trim($in['observacao']??'');
$validos=['AGENDADA','A_CAMINHO','EMBARQUE','EM_VIAGEM','FINALIZADA','CANCELADA'];if($id<=0||!in_array($novo,$validos,true))jsonResponse(false,'Status inválido.',[],400);
$conn->begin_transaction();
try{
    $s=$conn->prepare('SELECT codigo,origem,destino,status FROM viagens WHERE id=? AND empresa_id=3 FOR UPDATE');$s->bind_param('i',$id);$s->execute();$v=$s->get_result()->fetch_assoc();if(!$v)throw new RuntimeException('Viagem não encontrada.');
    $antes=$v['status'];if($antes===$novo){$conn->rollback();jsonResponse(true,'A viagem já está nesse status.',['status'=>$novo]);}
    $up=$conn->prepare("UPDATE viagens SET status=?,data_finalizacao=CASE WHEN ?='FINALIZADA' THEN NOW() ELSE data_finalizacao END WHERE id=?");$up->bind_param('ssi',$novo,$novo,$id);$up->execute();
    $h=$conn->prepare('INSERT INTO viagens_historico(viagem_id,usuario_id,status_anterior,status_novo,observacao) VALUES(?,?,?,?,?)');$h->bind_param('iisss',$id,$u['id'],$antes,$novo,$obs);$h->execute();
    $desc=$v['codigo'].' · '.$antes.' → '.$novo;
    criarEventoENotificar($conn,3,$u['id'],'Status da viagem atualizado',$desc,'INFORMATIVO','VIAGEM',$id,null,'TRANSPORTE','STATUS_VIAGEM');
    registrarAuditoria($conn,$u['id'],3,'ATUALIZAR_STATUS_VIAGEM','VIAGEM',$id,$desc); enfileirarSheets($conn,3,'STATUS_VIAGEM','VIAGEM',$id);
    $conn->commit();jsonResponse(true,'Status atualizado.',['status'=>$novo]);
}catch(Throwable $e){$conn->rollback();jsonResponse(false,$e->getMessage(),[],400);}
