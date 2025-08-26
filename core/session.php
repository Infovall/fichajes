<?php
// core/session.php
// Fuerza zona horaria de España para todas las operaciones PHP
date_default_timezone_set('Europe/Madrid');

require_once __DIR__ . '/db.php';
$c = cfg();
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_name($c['session_name']);
  session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) ? true : $c['session_secure'],
    'httponly' => $c['session_http_only'],
    'samesite' => $c['session_same_site'],
  ]);
  session_start();
}
