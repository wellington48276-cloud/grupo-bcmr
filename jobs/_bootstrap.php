<?php
require_once __DIR__.'/../api/config/database.php';
require_once __DIR__.'/../api/eventos/helpers.php';
if (PHP_SAPI !== 'cli') {
    $expected=getenv('BCMR_JOB_TOKEN') ?: '';
    $given=$_GET['token'] ?? '';
    if($expected==='' || !hash_equals($expected,$given)){http_response_code(403);exit('Acesso negado');}
}
header('Content-Type: application/json; charset=utf-8');
