<?php
require_once '../config/database.php'; require_once '../config/auth.php'; require_once '../eventos/helpers.php';
$u=requireAuth(); requireCsrf();
if(!usuarioTemAcessoEmpresa($conn,$u['id'],2,$u['perfil'])) jsonResponse(false,'Sem acesso à Manutenções Marques.',[],403);
$in=readJson(); $equipamento=trim($in['equipamento']??''); $cliente=trim($in['cliente']??''); $problema=trim($in['problema']??''); $prazo=trim($in['prazo']??'');
if($equipamento===''||$cliente==='') jsonResponse(false,'Informe cliente e equipamento.',[],400);
if($prazo==='')$prazo=date('Y-m-d H:i:s',strtotime('+3 days')); else $prazo=str_replace('T',' ',$prazo).(strlen($prazo)===16?':00':'');
$conn->begin_transaction();
try{
    $protocolo='MM-'.date('YmdHis').'-'.random_int(100,999);
    $s=$conn->prepare("INSERT INTO servicos(empresa_id,protocolo,cliente_nome,equipamento,problema,status,prazo) VALUES(2,?,?,?,?,'RECEBIDO',?)");$s->bind_param('sssss',$protocolo,$cliente,$equipamento,$problema,$prazo);$s->execute();$id=(int)$conn->insert_id;
    $hist=$conn->prepare("INSERT INTO servicos_historico(servico_id,usuario_id,status_anterior,status_novo,observacao) VALUES(?,?,'','RECEBIDO',?)");$obs='Serviço aberto no painel administrativo';$hist->bind_param('iis',$id,$u['id'],$obs);$hist->execute();
    $desc=$protocolo.' · '.$equipamento.' · '.$cliente;
    criarEventoENotificar($conn,2,$u['id'],'Novo serviço',$desc,'INFORMATIVO','SERVICO',$id,null,'MANUTENCAO','NOVO_SERVICO');
    registrarAuditoria($conn,$u['id'],2,'CRIAR_SERVICO','SERVICO',$id,$desc); enfileirarSheets($conn,2,'NOVO_SERVICO','SERVICO',$id);
    $conn->commit();jsonResponse(true,'Serviço cadastrado.',['servico_id'=>$id,'protocolo'=>$protocolo],201);
}catch(Throwable $e){$conn->rollback();jsonResponse(false,'Erro ao cadastrar serviço: '.$e->getMessage(),[],400);}
