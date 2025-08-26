<?php
// probe_index.php — autodiagnóstico del router
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');

echo "PHP_VERSION=", PHP_VERSION, "\n";

$check = [
  '/core/auth.php',
  '/core/helpers.php',
  '/core/csrf.php',
  '/core/db.php',
  '/views/header.php',
  '/views/footer.php',
  '/views/login.php',
  '/views/employee.php',
  '/views/admin.php',
  '/views/admin_leave_requests.php',
];
foreach ($check as $rel) {
  $path = __DIR__ . $rel;
  echo (is_file($path) ? "OK   " : "FAIL "), $rel, "\n";
}

echo "\nIntentando cargar header y footer...\n";
include __DIR__ . '/views/header.php';
include __DIR__ . '/views/footer.php';
echo "Header/Footer OK\n";

echo "\nIntentando cargar admin_leave_requests.php...\n";
include __DIR__ . '/views/admin_leave_requests.php';
echo "admin_leave_requests OK\n";

echo "\nFIN AUTODIAGNOSTICO\n";
