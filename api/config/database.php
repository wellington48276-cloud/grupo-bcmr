<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = getenv('BCMR_DB_HOST') ?: 'localhost';
$user = getenv('BCMR_DB_USER') ?: 'root';
$pass = getenv('BCMR_DB_PASS') ?: '';
$db   = getenv('BCMR_DB_NAME') ?: 'bcmr';
$port = (int) (getenv('BCMR_DB_PORT') ?: 3306);

try {
    $conn = new mysqli($host, $user, $pass, $db, $port);
    $conn->set_charset('utf8mb4');
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status'=>false,'mensagem'=>'Falha na conexão com o banco. Configure BCMR_DB_HOST, BCMR_DB_USER, BCMR_DB_PASS e BCMR_DB_NAME.'], JSON_UNESCAPED_UNICODE);
    exit;
}
