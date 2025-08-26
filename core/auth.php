<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
function user(){ return $_SESSION['user'] ?? null; }
function require_login(){ if(!user()){ redirect('index.php?page=login'); } }
function require_role($role){ if(!user() || (user()['role']??'') !== $role){ redirect('index.php'); } }
function signin($email,$pass){
  $pdo = db();
  $stmt = $pdo->prepare("SELECT * FROM " . pfx('users') . " WHERE email=? LIMIT 1");
  $stmt->execute([strtolower($email)]);
  $u = $stmt->fetch();
  if(!$u) return [false, 'Usuario o contraseña inválidos'];
  $ok = password_verify($pass, $u['password_hash']);
  if (!$ok){ $ok = hash_equals(crypt($pass, $u['password_hash']), $u['password_hash']); }
  if($ok){ $_SESSION['user'] = $u; return [true, 'ok']; }
  return [false, 'Usuario o contraseña inválidos'];
}
function signout(){ $_SESSION = []; if (ini_get("session.use_cookies")) {
  $params = session_get_cookie_params();
  setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
} session_destroy(); }
