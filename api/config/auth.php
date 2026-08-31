<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/response.php';

function requireAuth(): array {
    if (empty($_SESSION['usuario_id'])) {
        jsonResponse(false, 'Não autenticado.', [], 401);
    }
    return [
        'id'=>(int)$_SESSION['usuario_id'],
        'nome'=>$_SESSION['usuario_nome'] ?? '',
        'perfil'=>$_SESSION['perfil'] ?? 'FUNCIONARIO'
    ];
}
function csrfToken(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
    return $_SESSION['csrf'];
}
function requireCsrf(): void {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        jsonResponse(false, 'Token de segurança inválido.', [], 419);
    }
}
function usuarioTemAcessoEmpresa(mysqli $conn, int $usuarioId, int $empresaId, string $perfil): bool {
    if ($perfil === 'ADMIN_GERAL') return true;
    $stmt = $conn->prepare('SELECT 1 FROM usuario_empresas WHERE usuario_id=? AND empresa_id=? LIMIT 1');
    $stmt->bind_param('ii', $usuarioId, $empresaId);
    $stmt->execute();
    return (bool)$stmt->get_result()->fetch_row();
}
