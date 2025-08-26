<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);
$c = require __DIR__ . '/config.php';
echo "CFG host={$c['db_host']} db={$c['db_name']} user={$c['db_user']}\n";
try {
  $pdo = new PDO("mysql:host={$c['db_host']};dbname={$c['db_name']};charset=utf8mb4", $c['db_user'], $c['db_pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  echo "PDO OK\n";
  foreach($pdo->query("SHOW TABLES") as $r){ echo $r[0],"\n"; }
} catch(Throwable $e){ echo "PDO ERROR: ", $e->getMessage(); }
