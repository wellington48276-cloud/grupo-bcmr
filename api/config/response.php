<?php
header('Content-Type: application/json; charset=utf-8');
function jsonResponse(bool $status, string $mensagem='', array $dados=[], int $httpCode=200): never {
    http_response_code($httpCode);
    echo json_encode(['status'=>$status,'mensagem'=>$mensagem,'dados'=>$dados], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}
function readJson(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '{}', true);
    return is_array($data) ? $data : [];
}
