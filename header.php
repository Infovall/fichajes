
<?php
// header.php — Navegación con enlace a Usuarios
declare(strict_types=1);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ClockIt</title>
  <style>
    body{margin:0;background:#0b1220;color:#e5e7eb;font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,"Helvetica Neue",Arial}
    a{color:#c7d2fe;text-decoration:none}
    a:hover{text-decoration:underline}
    header{position:sticky;top:0;background:#0f172a;border-bottom:1px solid #1f2937;z-index:50}
    .wrap{max-width:1100px;margin:0 auto;padding:0 1rem}
    .nav{display:flex;align-items:center;justify-content:space-between;height:56px}
    .brand{font-weight:700;letter-spacing:.2px}
    .menu{display:flex;gap:1rem;align-items:center;flex-wrap:wrap}
    .menu a{padding:.35rem .6rem;border-radius:8px;border:1px solid transparent}
    .menu a.active{background:#111827;border-color:#334155;text-decoration:none}
    main{max-width:1100px;margin:1rem auto;padding:0 1rem}
    .card{background:#0f172a;border:1px solid #1f2937;border-radius:14px;padding:1rem;margin:.75rem 0}
    .btn{display:inline-block;padding:.45rem .7rem;border-radius:10px;border:1px solid #334155;background:#111827;color:#e5e7eb}
    .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1rem}
    .small{font-size:.85rem;opacity:.9}
  </style>
</head>
<body>
<header>
  <div class="wrap nav">
    <div class="brand">⏱️ ClockIt</div>
    <nav class="menu">
      <a href="index.php" class="<?= (!isset($_GET['page']) || $_GET['page']==='') ? 'active' : '' ?>">Inicio</a>
      <?php if (has_role('employee')): ?>
        <a href="index.php?page=employee" class="<?= (($_GET['page'] ?? '')==='employee') ? 'active' : '' ?>">Portal empleado</a>
      <?php endif; ?>

      <?php if (has_role('admin')): ?>
        <a href="index.php?page=admin" class="<?= (($_GET['page'] ?? '')==='admin') ? 'active' : '' ?>">Admin</a>
        <a href="index.php?page=admin_hours" class="<?= (($_GET['page'] ?? '')==='admin_hours') ? 'active' : '' ?>">Horas</a>
        <a href="index.php?page=admin_calendar" class="<?= (($_GET['page'] ?? '')==='admin_calendar') ? 'active' : '' ?>">Calendario</a>
        <a href="index.php?page=admin_schedules" class="<?= (($_GET['page'] ?? '')==='admin_schedules') ? 'active' : '' ?>">Horarios</a>
        <a href="index.php?page=admin_leave_requests" class="<?= (($_GET['page'] ?? '')==='admin_leave_requests') ? 'active' : '' ?>">Solicitudes</a>
       <a href="index.php?page=admin_users"
   class="<?= (($_GET['page'] ?? '')==='admin_users') ? 'active' : '' ?>">
   🧑‍🤝‍🧑 Usuarios
</a>

      <a href="index.php?page=logout">Salir</a>
    </nav>
  </div>
</header>
<main>
