<?php
$files=['verificar_servicos_atrasados.php','verificar_servicos_proximos.php','verificar_viagens_proximas.php','verificar_viagens_atrasadas.php'];
if(PHP_SAPI!=='cli'){http_response_code(400);exit('Execute os jobs individualmente via cron ou use este agregador pelo CLI.');}
foreach($files as $f){echo "== $f ==\n";passthru(PHP_BINARY.' '.escapeshellarg(__DIR__.'/'.$f));echo "\n";}
