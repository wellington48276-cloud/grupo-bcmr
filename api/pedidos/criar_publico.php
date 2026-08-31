<?php
require_once '../config/database.php';
require_once '../config/response.php';
require_once '../eventos/helpers.php';
$in=readJson();
$cliente=$in['cliente']??[];$itens=$in['itens']??[];$forma=strtoupper(trim($in['forma_pagamento']??'PIX'));$gateway=trim($in['gateway']??'MANUAL');
if(!$itens||!is_array($itens)) jsonResponse(false,'Carrinho vazio.',[],400);
if(!in_array($forma,['PIX','CARTAO','DINHEIRO','LINK_PAGAMENTO','OUTRO'],true)) jsonResponse(false,'Forma de pagamento inválida.',[],400);
$conn->begin_transaction();
try{
  $clienteId=null;$nome=trim($cliente['nome']??'');
  if($nome!==''){$cpf=trim($cliente['cpf_cnpj']??'');$tel=trim($cliente['telefone']??'');$email=trim($cliente['email']??'');$s=$conn->prepare('INSERT INTO clientes(nome,cpf_cnpj,telefone,email) VALUES(?,?,?,?)');$s->bind_param('ssss',$nome,$cpf,$tel,$email);$s->execute();$clienteId=(int)$conn->insert_id;}
  $codigo='CM-'.date('ymd').'-'.strtoupper(substr(bin2hex(random_bytes(4)),0,6));
  $s=$conn->prepare("INSERT INTO pedidos(empresa_id,cliente_id,codigo,status,subtotal,desconto,total,estoque_reservado,reserva_expira_em) VALUES(1,?,?,'AGUARDANDO_PAGAMENTO',0,0,0,1,DATE_ADD(NOW(),INTERVAL 30 MINUTE))");
  $s->bind_param('is',$clienteId,$codigo);$s->execute();$pedidoId=(int)$conn->insert_id;$total=0.0;
  foreach($itens as $item){$pid=(int)($item['produto_id']??0);$qtd=(int)($item['quantidade']??0);if($pid<1||$qtd<1)throw new RuntimeException('Item inválido.');
    $p=$conn->prepare('SELECT id,nome,preco_venda,estoque_atual FROM produtos WHERE id=? AND empresa_id=1 AND ativo=1 FOR UPDATE');$p->bind_param('i',$pid);$p->execute();$prod=$p->get_result()->fetch_assoc();if(!$prod)throw new RuntimeException('Produto não encontrado.');if((int)$prod['estoque_atual']<$qtd)throw new RuntimeException('Estoque insuficiente para '.$prod['nome'].'.');
    $preco=(float)$prod['preco_venda'];$sub=$preco*$qtd;$total+=$sub;$pi=$conn->prepare('INSERT INTO pedido_itens(pedido_id,produto_id,quantidade,preco_unitario,subtotal) VALUES(?,?,?,?,?)');$pi->bind_param('iiidd',$pedidoId,$pid,$qtd,$preco,$sub);$pi->execute();
    $antes=(int)$prod['estoque_atual'];$depois=$antes-$qtd;$up=$conn->prepare('UPDATE produtos SET estoque_atual=? WHERE id=?');$up->bind_param('ii',$depois,$pid);$up->execute();$m=$conn->prepare("INSERT INTO estoque_movimentacoes(empresa_id,produto_id,tipo,quantidade,estoque_anterior,estoque_posterior,referencia,observacao) VALUES(1,?,'VENDA',?,?,?,?,'Reserva de pedido')");$ref=$codigo;$neg=-$qtd;$m->bind_param('iiiis',$pid,$neg,$antes,$depois,$ref);$m->execute();$movId=(int)$conn->insert_id;enfileirarSheets($conn,1,'MOVIMENTACAO_ESTOQUE','ESTOQUE_MOVIMENTACAO',$movId);
  }
  $u=$conn->prepare('UPDATE pedidos SET subtotal=?,total=? WHERE id=?');$u->bind_param('ddi',$total,$total,$pedidoId);$u->execute();
  $idem='PEDIDO-'.$pedidoId.'-'.$forma;$pg=$conn->prepare("INSERT INTO pagamentos(empresa_id,pedido_id,forma,gateway,valor,status,idempotency_key) VALUES(1,?,?,?,?, 'PENDENTE',?)");$pg->bind_param('issds',$pedidoId,$forma,$gateway,$total,$idem);$pg->execute();$pagamentoId=(int)$conn->insert_id;
  criarEventoENotificar($conn,1,0,'Novo pedido '.$codigo,'Novo pedido aguardando pagamento: R$ '.number_format($total,2,',','.'),'ATENCAO','PEDIDO',$pedidoId,'NOVO_PEDIDO:'.$pedidoId,'VENDAS','NOVO_PEDIDO');enfileirarSheets($conn,1,'PEDIDO','PEDIDO',$pedidoId);enfileirarSheets($conn,1,'PAGAMENTO','PAGAMENTO',$pagamentoId);
  $conn->commit();jsonResponse(true,'Pedido criado.', ['pedido_id'=>$pedidoId,'codigo'=>$codigo,'pagamento_id'=>$pagamentoId,'total'=>$total,'reserva_minutos'=>30]);
}catch(Throwable $e){$conn->rollback();jsonResponse(false,$e->getMessage(),[],400);}
