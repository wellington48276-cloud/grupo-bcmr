<?php
require_once '../config/database.php'; require_once '../config/auth.php'; require_once '../eventos/helpers.php';
$u=requireAuth(); requireCsrf();
if(!usuarioTemAcessoEmpresa($conn,$u['id'],2,$u['perfil'])) jsonResponse(false,'Sem acesso à Manutenções Marques.',[],403);
$in=readJson(); $id=(int)($in['servico_id']??0); $novo=strtoupper(trim($in['status']??'')); $obs=trim($in['observacao']??'');
$validos=['RECEBIDO','EM_ANALISE','AGUARDANDO_ORCAMENTO','AGUARDANDO_APROVACAO','EM_MANUTENCAO','FINALIZADO','ENTREGUE','CANCELADO'];
if($id<=0||!in_array($novo,$validos,true)) jsonResponse(false,'Status inválido.',[],400);
$conn->begin_transaction();
try{
    $s=$conn->prepare('SELECT protocolo,equipamento,status FROM servicos WHERE id=? AND empresa_id=2 FOR UPDATE');$s->bind_param('i',$id);$s->execute();$serv=$s->get_result()->fetch_assoc();if(!$serv)throw new RuntimeException('Serviço não encontrado.');
    $antes=$serv['status']; if($antes===$novo){$conn->rollback();jsonResponse(true,'O serviço já está nesse status.',['status'=>$novo]);}
    $up=$conn->prepare("UPDATE servicos SET status=?,finalizado_em=CASE WHEN ?='FINALIZADO' THEN NOW() ELSE finalizado_em END WHERE id=?");$up->bind_param('ssi',$novo,$novo,$id);$up->execute();
    $h=$conn->prepare('INSERT INTO servicos_historico(servico_id,usuario_id,status_anterior,status_novo,observacao) VALUES(?,?,?,?,?)');$h->bind_param('iisss',$id,$u['id'],$antes,$novo,$obs);$h->execute();
    $desc=$serv['protocolo'].' · '.$antes.' → '.$novo;
    criarEventoENotificar($conn,2,$u['id'],'Status do serviço atualizado',$desc,'INFORMATIVO','SERVICO',$id,null,'MANUTENCAO','STATUS_SERVICO');
    registrarAuditoria($conn,$u['id'],2,'ATUALIZAR_STATUS_SERVICO','SERVICO',$id,$desc);
    enfileirarSheets($conn,2,'STATUS_SERVICO','SERVICO',$id);
    $conn->commit();jsonResponse(true,'Status atualizado.',['status'=>$novo]);
}catch(Throwable $e){$conn->rollback();jsonResponse(false,$e->getMessage(),[],400);}
