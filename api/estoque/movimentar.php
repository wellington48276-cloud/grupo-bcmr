<?php
require_once '../config/database.php'; require_once '../config/auth.php'; require_once '../eventos/helpers.php';
$u=requireAuth(); requireCsrf();
if(!usuarioTemAcessoEmpresa($conn,$u['id'],1,$u['perfil'])) jsonResponse(false,'Sem acesso à Comercial Marques.',[],403);
$in=readJson(); $produtoId=(int)($in['produto_id']??0); $tipo=strtoupper(trim($in['tipo']??''));
$quantidade=(int)($in['quantidade']??0); $obs=trim($in['observacao']??'');
$permitidos=['ENTRADA','SAIDA_MANUAL','DEVOLUCAO','PERDA','AJUSTE'];
if($produtoId<=0||!in_array($tipo,$permitidos,true)||$quantidade<0) jsonResponse(false,'Movimentação inválida.',[],400);
if($tipo!=='AJUSTE' && $quantidade<=0) jsonResponse(false,'A quantidade deve ser maior que zero.',[],400);
$conn->begin_transaction();
try{
    $s=$conn->prepare('SELECT id,nome,estoque_atual,estoque_minimo FROM produtos WHERE id=? AND empresa_id=1 AND ativo=1 FOR UPDATE');
    $s->bind_param('i',$produtoId); $s->execute(); $p=$s->get_result()->fetch_assoc(); if(!$p) throw new RuntimeException('Produto não encontrado.');
    $antes=(int)$p['estoque_atual'];
    if($tipo==='ENTRADA'||$tipo==='DEVOLUCAO') $depois=$antes+$quantidade;
    elseif($tipo==='AJUSTE') $depois=$quantidade;
    else $depois=$antes-$quantidade;
    if($depois<0) throw new RuntimeException('Estoque insuficiente.');
    $up=$conn->prepare('UPDATE produtos SET estoque_atual=? WHERE id=?'); $up->bind_param('ii',$depois,$produtoId); $up->execute();
    $ref='MOV-'.date('YmdHis'); $movQtd=$tipo==='AJUSTE'?abs($depois-$antes):$quantidade;
    $m=$conn->prepare('INSERT INTO estoque_movimentacoes(empresa_id,produto_id,tipo,quantidade,estoque_anterior,estoque_posterior,referencia,observacao,usuario_id) VALUES(1,?,?,?,?,?,?,?,?)');
    $m->bind_param('isiiissi',$produtoId,$tipo,$movQtd,$antes,$depois,$ref,$obs,$u['id']); $m->execute(); $movId=(int)$conn->insert_id;
    $descricao=$p['nome'].' · '.$antes.' → '.$depois.' · '.$tipo;
    criarEventoENotificar($conn,1,$u['id'],'Estoque atualizado',$descricao,'INFORMATIVO','PRODUTO',$produtoId,null,'ESTOQUE','MOVIMENTACAO_ESTOQUE');
    if($depois===0) criarEventoENotificar($conn,1,$u['id'],'Produto sem estoque',$p['nome'].' ficou sem estoque.','CRITICO','PRODUTO',$produtoId,'ESTOQUE_ZERADO:'.$produtoId.':'.$movId,'ESTOQUE','ESTOQUE_ZERADO');
    elseif($depois<=(int)$p['estoque_minimo']) criarEventoENotificar($conn,1,$u['id'],'Estoque baixo',$p['nome'].' possui '.$depois.' unidade(s).','ATENCAO','PRODUTO',$produtoId,'ESTOQUE_BAIXO:'.$produtoId.':'.$movId,'ESTOQUE','ESTOQUE_BAIXO');
    registrarAuditoria($conn,$u['id'],1,'MOVIMENTAR_ESTOQUE','PRODUTO',$produtoId,$descricao);
    enfileirarSheets($conn,1,'MOVIMENTACAO_ESTOQUE','ESTOQUE_MOVIMENTACAO',$movId);
    $conn->commit(); jsonResponse(true,'Estoque atualizado.',['estoque_anterior'=>$antes,'estoque_atual'=>$depois,'movimentacao_id'=>$movId]);
}catch(Throwable $e){$conn->rollback();jsonResponse(false,$e->getMessage(),[],400);}
