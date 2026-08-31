<?php
require_once '../config/database.php';
require_once '../config/response.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$in = readJson();
$email = trim(strtolower($in['email'] ?? ''));
$senha = (string)($in['senha'] ?? '');
if (!$email || !$senha) jsonResponse(false,'Informe e-mail e senha.',[],400);
$stmt = $conn->prepare('SELECT id,nome,email,senha_hash,perfil,ativo FROM usuarios WHERE email=? LIMIT 1');
$stmt->bind_param('s',$email); $stmt->execute(); $u=$stmt->get_result()->fetch_assoc();
if (!$u || !(int)$u['ativo'] || !password_verify($senha,$u['senha_hash'])) jsonResponse(false,'E-mail ou senha inválidos.',[],401);
session_regenerate_id(true);
$_SESSION['usuario_id']=(int)$u['id']; $_SESSION['usuario_nome']=$u['nome']; $_SESSION['perfil']=$u['perfil']; $_SESSION['csrf']=bin2hex(random_bytes(24));
jsonResponse(true,'Login realizado.',['usuario'=>['id'=>(int)$u['id'],'nome'=>$u['nome'],'email'=>$u['email'],'perfil'=>$u['perfil']],'csrf'=>$_SESSION['csrf']]);
