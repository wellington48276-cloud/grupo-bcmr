<?php
function criarEventoENotificar(
    mysqli $conn,
    int $empresaId,
    int $usuarioId,
    string $titulo,
    string $descricao,
    string $prioridade,
    string $entidadeTipo,
    int $entidadeId,
    ?string $chaveUnica = null,
    ?string $modulo = null,
    ?string $tipo = null
): int {
    if ($chaveUnica) {
        $q = $conn->prepare('SELECT id FROM eventos WHERE chave_unica=? LIMIT 1');
        $q->bind_param('s', $chaveUnica);
        $q->execute();
        if ($row = $q->get_result()->fetch_assoc()) return (int)$row['id'];
    }

    $s = $conn->prepare('INSERT INTO eventos(empresa_id,modulo,tipo,titulo,descricao,prioridade,entidade_tipo,entidade_id,chave_unica) VALUES(?,?,?,?,?,?,?,?,?)');
    $s->bind_param('issssssis', $empresaId, $modulo, $tipo, $titulo, $descricao, $prioridade, $entidadeTipo, $entidadeId, $chaveUnica);
    $s->execute();
    $eventoId = (int)$conn->insert_id;

    $usuarios = $conn->prepare("SELECT DISTINCT u.id FROM usuarios u LEFT JOIN usuario_empresas ue ON ue.usuario_id=u.id WHERE u.ativo=1 AND (u.perfil='ADMIN_GERAL' OR ue.empresa_id=?)");
    $usuarios->bind_param('i', $empresaId);
    $usuarios->execute();
    $r = $usuarios->get_result();
    $n = $conn->prepare('INSERT INTO notificacoes(empresa_id,usuario_id,evento_id,titulo,mensagem,prioridade) VALUES(?,?,?,?,?,?)');
    while ($row = $r->fetch_assoc()) {
        $uid = (int)$row['id'];
        $n->bind_param('iiisss', $empresaId, $uid, $eventoId, $titulo, $descricao, $prioridade);
        $n->execute();
        if (in_array($prioridade, ['ATENCAO','CRITICO'], true)) {
            $p = $conn->prepare("INSERT INTO push_envios(usuario_id,evento_id,titulo,mensagem,prioridade,status) VALUES(?,?,?,?,?,'PENDENTE')");
            $p->bind_param('iisss', $uid, $eventoId, $titulo, $descricao, $prioridade);
            $p->execute();
        }
    }
    return $eventoId;
}

function registrarAuditoria(mysqli $conn, int $usuarioId, int $empresaId, string $acao, string $entidadeTipo, int $entidadeId, string $descricao=''): void {
    $ip = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45);
    $s = $conn->prepare('INSERT INTO auditoria(usuario_id,empresa_id,acao,entidade_tipo,entidade_id,descricao,ip) VALUES(?,?,?,?,?,?,?)');
    $s->bind_param('iississ', $usuarioId, $empresaId, $acao, $entidadeTipo, $entidadeId, $descricao, $ip);
    $s->execute();
}

function enfileirarSheets(mysqli $conn, int $empresaId, string $tipo, string $entidadeTipo, int $entidadeId): void {
    $s = $conn->prepare("INSERT INTO integracao_sheets(empresa_id,tipo,entidade_tipo,entidade_id,status) VALUES(?,?,?,?,'PENDENTE')");
    $s->bind_param('issi', $empresaId, $tipo, $entidadeTipo, $entidadeId);
    $s->execute();
}
