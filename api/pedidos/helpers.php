<?php
function liberarReservaPedido(mysqli $conn,int $pedidoId,string $motivo='Cancelamento'): void {
  $s=$conn->prepare('SELECT codigo,status,estoque_reservado FROM pedidos WHERE id=? FOR UPDATE');$s->bind_param('i',$pedidoId);$s->execute();$p=$s->get_result()->fetch_assoc();if(!$p||!(int)$p['estoque_reservado'])return;
  $it=$conn->prepare('SELECT pi.produto_id,pi.quantidade,pr.estoque_atual FROM pedido_itens pi JOIN produtos pr ON pr.id=pi.produto_id WHERE pi.pedido_id=? FOR UPDATE');$it->bind_param('i',$pedidoId);$it->execute();$r=$it->get_result();
  while($x=$r->fetch_assoc()){$pid=(int)$x['produto_id'];$q=(int)$x['quantidade'];$antes=(int)$x['estoque_atual'];$depois=$antes+$q;$u=$conn->prepare('UPDATE produtos SET estoque_atual=? WHERE id=?');$u->bind_param('ii',$depois,$pid);$u->execute();$m=$conn->prepare("INSERT INTO estoque_movimentacoes(empresa_id,produto_id,tipo,quantidade,estoque_anterior,estoque_posterior,referencia,observacao) VALUES(1,?,'DEVOLUCAO',?,?,?,?,?)");$ref=$p['codigo'];$m->bind_param('iiiiss',$pid,$q,$antes,$depois,$ref,$motivo);$m->execute();}
  $u=$conn->prepare('UPDATE pedidos SET estoque_reservado=0,reserva_expira_em=NULL WHERE id=?');$u->bind_param('i',$pedidoId);$u->execute();
}
function confirmarReservaPedido(mysqli $conn,int $pedidoId): void {$u=$conn->prepare("UPDATE pedidos SET estoque_reservado=0,reserva_expira_em=NULL,status='PAGO' WHERE id=?");$u->bind_param('i',$pedidoId);$u->execute();}
