<?php
require_once '../config/database.php';require_once '../config/response.php';$q=$conn->query("SELECT p.id,p.sku,p.nome,p.marca,p.modelo,p.preco_venda,p.estoque_atual,c.nome categoria FROM produtos p LEFT JOIN categorias c ON c.id=p.categoria_id WHERE p.empresa_id=1 AND p.ativo=1 AND p.estoque_atual>0 ORDER BY p.nome");jsonResponse(true,'',['produtos'=>$q->fetch_all(MYSQLI_ASSOC)]);
