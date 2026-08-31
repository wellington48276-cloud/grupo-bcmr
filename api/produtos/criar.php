<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../eventos/helpers.php';
$u=requireAuth(); requireCsrf();
if(!usuarioTemAcessoEmpresa($conn,$u['id'],1,$u['perfil'])) jsonResponse(false,'Sem acesso à Comercial Marques.',[],403);
$in=readJson();
$nome=trim($in['nome']??''); $sku=trim($in['sku']??''); $categoria=(int)($in['categoria_id']??0);
$marca=trim($in['marca']??''); $modelo=trim($in['modelo']??''); $pv=(float)($in['preco_venda']??0);
$pc=(float)($in['preco_compra']??0); $est=(int)($in['estoque_inicial']??0); $min=(int)($in['estoque_minimo']??0);
if($nome===''||$sku===''||$est<0||$min<0||$pv<0||$pc<0) jsonResponse(false,'Revise nome, SKU, preços e estoque.',[],400);
$conn->begin_transaction();
try{
    $s=$conn->prepare('INSERT INTO produtos(empresa_id,categoria_id,sku,nome,marca,modelo,preco_compra,preco_venda,estoque_atual,estoque_minimo) VALUES(1,NULLIF(?,0),?,?,?,?,?,?,?,?)');
    $s->bind_param('isssddii',$categoria,$sku,$nome,$marca,$modelo,$pc,$pv,$est,$min); $s->execute(); $id=(int)$conn->insert_id;
    if($est>0){
        $tipo='ENTRADA'; $ref='CADASTRO_INICIAL'; $obs='Estoque inicial'; $zero=0;
        $m=$conn->prepare('INSERT INTO estoque_movimentacoes(empresa_id,produto_id,tipo,quantidade,estoque_anterior,estoque_posterior,referencia,observacao,usuario_id) VALUES(1,?,?,?,?,?,?,?,?)');
        $m->bind_param('isiiissi',$id,$tipo,$est,$zero,$est,$ref,$obs,$u['id']); $m->execute();
    }
    $desc=$nome.' · SKU '.$sku.' · estoque inicial '.$est;
    criarEventoENotificar($conn,1,$u['id'],'Novo produto cadastrado',$desc,'INFORMATIVO','PRODUTO',$id,null,'COMERCIAL','NOVO_PRODUTO');
    if($est===0) criarEventoENotificar($conn,1,$u['id'],'Produto sem estoque',$nome.' foi cadastrado com estoque zerado.','CRITICO','PRODUTO',$id,'ESTOQUE_ZERADO:'.$id,'ESTOQUE','ESTOQUE_ZERADO');
    elseif($est<=$min) criarEventoENotificar($conn,1,$u['id'],'Estoque baixo',$nome.' possui '.$est.' unidade(s).','ATENCAO','PRODUTO',$id,'ESTOQUE_BAIXO:'.$id.':'.$est,'ESTOQUE','ESTOQUE_BAIXO');
    registrarAuditoria($conn,$u['id'],1,'CRIAR_PRODUTO','PRODUTO',$id,$desc);
    enfileirarSheets($conn,1,'PRODUTO','PRODUTO',$id);
    $conn->commit(); jsonResponse(true,'Produto cadastrado.',['produto_id'=>$id],201);
}catch(Throwable $e){$conn->rollback();jsonResponse(false,'Erro ao cadastrar produto: '.$e->getMessage(),[],400);}
