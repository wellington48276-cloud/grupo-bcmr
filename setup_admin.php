<?php
require_once __DIR__.'/api/config/database.php';
if (PHP_SAPI === 'cli') {
    $email = $argv[1] ?? 'admin@bcmr.local';
    $senha = $argv[2] ?? '';
    $nome  = $argv[3] ?? 'Administrador BCMR';
} else {
    http_response_code(403); exit('Execute pelo terminal: php setup_admin.php email senha "Nome"');
}
if (strlen($senha) < 10) exit("Use uma senha com pelo menos 10 caracteres.\n");
$hash=password_hash($senha,PASSWORD_DEFAULT);
$stmt=$conn->prepare("INSERT INTO usuarios(nome,email,senha_hash,perfil,ativo) VALUES(?,?,?,'ADMIN_GERAL',1) ON DUPLICATE KEY UPDATE nome=VALUES(nome),senha_hash=VALUES(senha_hash),perfil='ADMIN_GERAL',ativo=1");
$stmt->bind_param('sss',$nome,$email,$hash);$stmt->execute();echo "Administrador criado/atualizado: $email\n";
