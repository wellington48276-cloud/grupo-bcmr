<?php
require_once __DIR__.'/_bootstrap.php';

$endpoint = getenv('BCMR_SHEETS_WEBHOOK') ?: '';
$token = getenv('BCMR_SHEETS_TOKEN') ?: '';
if ($endpoint === '') {
    echo json_encode(['status'=>false,'mensagem'=>'Configure BCMR_SHEETS_WEBHOOK para ativar a sincronização.'], JSON_UNESCAPED_UNICODE);
    exit;
}

function payloadEntidade(mysqli $conn, array $item): array {
    $tipo = (string)$item['entidade_tipo'];
    $id = (int)$item['entidade_id'];

    if ($tipo === 'PRODUTO') {
        $s = $conn->prepare("SELECT p.id,p.empresa_id,p.categoria_id,c.nome categoria,p.sku,p.nome,p.marca,p.modelo,p.descricao,p.preco_compra,p.preco_venda,p.estoque_atual,p.estoque_minimo,p.ativo,p.criado_em,p.atualizado_em FROM produtos p LEFT JOIN categorias c ON c.id=p.categoria_id WHERE p.id=?");
        $s->bind_param('i', $id);
    } elseif ($tipo === 'ESTOQUE_MOVIMENTACAO') {
        $s = $conn->prepare("SELECT m.id,m.empresa_id,m.produto_id,m.tipo,m.quantidade,m.estoque_anterior,m.estoque_posterior,m.referencia,m.observacao,m.criado_em,p.sku,p.nome produto,p.marca,p.modelo,p.preco_venda,p.estoque_atual,p.estoque_minimo,c.nome categoria FROM estoque_movimentacoes m JOIN produtos p ON p.id=m.produto_id LEFT JOIN categorias c ON c.id=p.categoria_id WHERE m.id=?");
        $s->bind_param('i', $id);
    } elseif ($tipo === 'SERVICO') {
        $s = $conn->prepare("SELECT id,empresa_id,protocolo,cliente_nome,equipamento,numero_serie,problema,diagnostico,status,prazo,valor_estimado,valor_final,criado_em,atualizado_em,finalizado_em FROM servicos WHERE id=?");
        $s->bind_param('i', $id);
    } elseif ($tipo === 'VIAGEM') {
        $s = $conn->prepare("SELECT id,empresa_id,codigo,cliente_nome,origem,destino,data_saida,data_finalizacao,passageiros,tipo,observacao,status,valor,criado_em,atualizado_em FROM viagens WHERE id=?");
        $s->bind_param('i', $id);
    } elseif ($tipo === 'PEDIDO') {
        $s = $conn->prepare("SELECT p.id,p.empresa_id,p.cliente_id,p.codigo,p.status,p.subtotal,p.desconto,p.total,p.estoque_reservado,p.reserva_expira_em,p.criado_em,p.atualizado_em,c.nome cliente_nome,c.cpf_cnpj,c.telefone,c.email FROM pedidos p LEFT JOIN clientes c ON c.id=p.cliente_id WHERE p.id=?");
        $s->bind_param('i', $id);
    } elseif ($tipo === 'PAGAMENTO') {
        $s = $conn->prepare("SELECT pg.id,pg.empresa_id,pg.pedido_id,p.codigo pedido_codigo,pg.forma,pg.gateway,pg.valor,pg.status,pg.identificador_externo,pg.pago_em,pg.criado_em,pg.atualizado_em FROM pagamentos pg LEFT JOIN pedidos p ON p.id=pg.pedido_id WHERE pg.id=?");
        $s->bind_param('i', $id);
    } elseif ($tipo === 'ORCAMENTO') {
        $s = $conn->prepare("SELECT o.id,o.empresa_id,o.servico_id,s.protocolo,o.codigo,o.descricao,o.valor_mao_obra,o.valor_pecas,o.valor_total,o.status,o.enviado_em,o.respondido_em,o.criado_em FROM orcamentos o LEFT JOIN servicos s ON s.id=o.servico_id WHERE o.id=?");
        $s->bind_param('i', $id);
    } elseif ($tipo === 'DOCUMENTO') {
        $s = $conn->prepare("SELECT id,empresa_id,tipo,entidade_tipo,entidade_id,nome_original,mime_type,tamanho_bytes,status_processamento,criado_em FROM documentos WHERE id=?");
        $s->bind_param('i', $id);
    } else {
        return [];
    }

    $s->execute();
    return $s->get_result()->fetch_assoc() ?: [];
}

function empresaNome(int $empresaId): string {
    return match ($empresaId) {
        1 => 'Comercial Marques',
        2 => 'Manutenções Marques',
        3 => 'Grupo BCMR Transportes',
        default => 'Grupo BCMR'
    };
}

function destinoSheets(array $item): array {
    $entidade = (string)$item['entidade_tipo'];
    $tipo = (string)$item['tipo'];

    return match ($entidade) {
        'PRODUTO' => ['aba'=>'Catálogo', 'operacao'=>'UPSERT'],
        'ESTOQUE_MOVIMENTACAO' => ['aba'=>'Movimentações Estoque', 'operacao'=>'APPEND'],
        'PEDIDO' => ['aba'=>'Vendas', 'operacao'=>'UPSERT'],
        'PAGAMENTO' => ['aba'=>'Pagamentos', 'operacao'=>'UPSERT'],
        'SERVICO' => ['aba'=>'Serviços', 'operacao'=>'UPSERT', 'historico'=>in_array($tipo, ['NOVO_SERVICO','SERVICO','STATUS_SERVICO'], true) ? 'Serviços Histórico' : null],
        'ORCAMENTO' => ['aba'=>'Orçamentos', 'operacao'=>'UPSERT'],
        'VIAGEM' => ['aba'=>'Viagens', 'operacao'=>'UPSERT', 'historico'=>in_array($tipo, ['NOVA_VIAGEM','VIAGEM','STATUS_VIAGEM'], true) ? 'Viagens Histórico' : null],
        'DOCUMENTO' => ['aba'=>'Documentos', 'operacao'=>'UPSERT'],
        default => ['aba'=>'Eventos Integração', 'operacao'=>'APPEND']
    };
}

function postJson(string $url, array $payload, string $token): array {
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $headers = "Content-Type: application/json\r\n";
    if ($token !== '') $url .= (str_contains($url, '?') ? '&' : '?').'token='.rawurlencode($token);
    $ctx = stream_context_create(['http'=>['method'=>'POST','header'=>$headers,'content'=>$body,'timeout'=>20,'ignore_errors'=>true]]);
    $out = @file_get_contents($url, false, $ctx);
    $status = 0;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) $status=(int)$m[1];
    return ['ok'=>$status>=200&&$status<300,'status'=>$status,'body'=>$out===false?'':$out];
}

$r = $conn->query("SELECT * FROM integracao_sheets WHERE status IN ('PENDENTE','ERRO') AND tentativa<5 ORDER BY id ASC LIMIT 50");
$ok=0; $erros=0;
while ($item=$r->fetch_assoc()) {
    $id=(int)$item['id'];
    $conn->query("UPDATE integracao_sheets SET status='PROCESSANDO',tentativa=tentativa+1 WHERE id=$id");
    try {
        $dados = payloadEntidade($conn,$item);
        if (!$dados) throw new RuntimeException('Entidade não encontrada para sincronização.');
        $destino = destinoSheets($item);
        $payload = [
            'schema_version'=>2,
            'fila_id'=>$id,
            'empresa_id'=>(int)$item['empresa_id'],
            'empresa'=>empresaNome((int)$item['empresa_id']),
            'tipo'=>$item['tipo'],
            'entidade_tipo'=>$item['entidade_tipo'],
            'entidade_id'=>(int)$item['entidade_id'],
            'aba_destino'=>$destino['aba'],
            'operacao'=>$destino['operacao'],
            'aba_historico'=>$destino['historico'] ?? null,
            'dados'=>$dados
        ];
        $res = postJson($endpoint,$payload,$token);
        if (!$res['ok']) throw new RuntimeException('HTTP '.$res['status'].' '.$res['body']);
        $s=$conn->prepare("UPDATE integracao_sheets SET status='SINCRONIZADO',erro=NULL,processado_em=NOW() WHERE id=?");
        $s->bind_param('i',$id); $s->execute(); $ok++;
    } catch (Throwable $e) {
        $msg=substr($e->getMessage(),0,2000);
        $s=$conn->prepare("UPDATE integracao_sheets SET status='ERRO',erro=? WHERE id=?");
        $s->bind_param('si',$msg,$id); $s->execute(); $erros++;
    }
}

echo json_encode(['status'=>true,'sincronizados'=>$ok,'erros'=>$erros],JSON_UNESCAPED_UNICODE);
