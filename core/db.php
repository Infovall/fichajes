<?php
// core/db.php
function cfg() { static $c=null; if(!$c){ $c = require __DIR__ . '/../config.php'; } return $c; }

function db() {
  static $pdo = null;
  if ($pdo) return $pdo;
  $c = cfg();
  $dsn = "mysql:host={$c['db_host']};dbname={$c['db_name']};charset=utf8mb4";
  $pdo = new PDO($dsn, $c['db_user'], $c['db_pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  // OPCIONAL: establece la zona horaria de MySQL (no afecta al guardado con UTC_TIMESTAMP)
  try { $pdo->exec("SET time_zone = 'Europe/Madrid'"); } catch (Throwable $e) { /* ignora si no está disponible */ }
  return $pdo;
}
function pfx($t){ return cfg()['table_prefix'] . $t; }
