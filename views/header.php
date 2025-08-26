<?php
// views/header.php — añade indicadores de pendientes (ajustes de fichaje y vacaciones) para ADMIN
if (!function_exists('user')) { require_once __DIR__ . '/../core/auth.php'; }
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/helpers.php';

$u = user();
$isEmployee = $u && ($u['role'] === 'employee');
$isAdmin = $u && ($u['role'] === 'admin');

$openNow = false;
$startedAtEs = null;
$startUtcIso = null;

if ($isEmployee) {
  $stmt = db()->prepare("SELECT clock_in FROM " . pfx('time_entries') . " WHERE user_id=? AND clock_out IS NULL ORDER BY clock_in DESC LIMIT 1");
  $stmt->execute([$u['id']]);
  if ($row = $stmt->fetch()) {
    $openNow = true;
    $dtUtc = new DateTime($row['clock_in'], new DateTimeZone('UTC'));
    $startedAtEs = $dtUtc->setTimezone(new DateTimeZone('Europe/Madrid'))->format('Y-m-d H:i:s');
    $startUtcIso = $dtUtc->setTimezone(new DateTimeZone('UTC'))->format('c');
  }
}

// Contadores para admin (solicitudes pendientes)
$pendingAdjust = 0; // clockit_time_adjust_requests.status='pending'
$pendingLeaves = 0; // clockit_leave_requests.status='pending'
if ($isAdmin) {
  try {
    $q1 = db()->query("SELECT COUNT(*) AS c FROM " . pfx('time_adjust_requests') . " WHERE status='pending'");
    $r1 = $q1->fetch(); $pendingAdjust = (int)($r1['c'] ?? 0);
  } catch (Throwable $e) { $pendingAdjust = 0; }
  try {
    $q2 = db()->query("SELECT COUNT(*) AS c FROM " . pfx('leave_requests') . " WHERE status='pending'");
    $r2 = $q2->fetch(); $pendingLeaves = (int)($r2['c'] ?? 0);
  } catch (Throwable $e) { $pendingLeaves = 0; }
  $pendingTotal = $pendingAdjust + $pendingLeaves;
}
?><!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ClockIt</title>
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  <link rel="stylesheet" href="public/app.css">
  <style>
    :root{
      --bg:#0b1220; --panel:#111827; --panel-2:#0f172a; --border:#1f2937;
      --ink:#e5e7eb; --muted:#9ca3af; --accent:#7c9bff; --ok:#22c55e; --danger:#ef4444; --warn:#f59e0b;
    }
    *{box-sizing:border-box} body{margin:0;background:var(--bg);color:var(--ink);font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,sans-serif;}
    a{color:var(--accent);text-decoration:none}
    .topbar{display:flex;align-items:center;gap:1rem;justify-content:space-between;padding:.7rem 1rem;border-bottom:1px solid var(--border);background:linear-gradient(180deg,var(--panel-2),#0c1530 85%);position:sticky;top:0;z-index:999}
    .brand{font-weight:800;letter-spacing:.2px;color:#fff;display:flex;align-items:center;gap:.5rem}
    .brand .logo{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:10px;background:#1e293b;color:#a5b4fc;border:1px solid #334155}
    .nav{display:flex;gap:.35rem;flex-wrap:wrap}
    .nav a{position:relative;display:inline-flex;align-items:center;gap:.45rem;padding:.45rem .7rem;border:1px solid #233047;border-radius:999px;color:#dbe3ff;background:rgba(124,155,255,0.08)}
    .nav a:hover{background:rgba(124,155,255,0.18);border-color:#2c3d5a}
    .badge-dot{position:absolute;top:-6px;right:-6px;min-width:18px;height:18px;padding:0 6px;border-radius:999px;background:#ef4444;color:#fff;border:1px solid #7f1d1d;font-size:.72rem;display:flex;align-items:center;justify-content:center;line-height:1}
    .pill{display:inline-flex;align-items:center;gap:.45rem;border-radius:999px;padding:.25rem .6rem;font-size:.85rem;border:1px solid #233047;background:#0b1430;color:#cbd5e1}
    .dot{width:.6rem;height:.6rem;border-radius:999px;display:inline-block}.dot.green{background:var(--ok)} .dot.red{background:var(--danger)}
    .who{font-size:.85rem;color:#c7d2fe;display:flex;align-items:center;gap:.6rem;flex-wrap:wrap}
    .timer{font-variant-numeric:tabular-nums}
    .container{max-width:1100px;margin:1rem auto;padding:0 1rem}
    .card{background:var(--panel);border:1px solid var(--border);border-radius:14px;padding:1rem;margin:.8rem 0;color:var(--ink)}
    .grid{display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(240px,1fr))}
    .btn{appearance:none;border:0;border-radius:10px;padding:.55rem .9rem;background:var(--accent);color:#0b1020;cursor:pointer;font-weight:600}
    .btn.outline{background:transparent;color:var(--ink);border:1px solid var(--border)}
    .btn.small{padding:.35rem .6rem;font-size:.82rem;border-radius:999px}
    table{width:100%;border-collapse:collapse;color:var(--ink)} thead th{font-weight:600;text-align:left;border-bottom:1px solid var(--border);padding:.6rem;color:#cbd5e1}
    tbody td{border-bottom:1px solid #0d1a33;padding:.55rem;color:#e5e7eb}
    .small{font-size:.85rem;color:var(--muted)} .badge{display:inline-block;background:#172554;color:#c7d2fe;border:1px solid #1e3a8a;border-radius:999px;padding:.15rem .5rem;font-size:.8rem}
    .actions{display:flex;gap:.6rem;flex-wrap:wrap;align-items:center} .kpi .value{font-size:1.8rem;font-weight:800} .kpi .hint{color:#9ca3af;margin-top:.2rem}
    footer{padding:2rem 1rem;color:#94a3b8}
  </style>
</head>
<body>
  <div class="topbar">
    <div class="brand"><span class="logo">⏱️</span> <span>ClockIt</span></div>
    <nav class="nav">
      <?php if ($u): ?>
        <?php if ($isAdmin): ?>
          <a href="index.php?page=admin">🏠 Admin</a>
          <a href="index.php?page=admin_hours">⏳ Horas</a>
          <a href="index.php?page=admin_totals">📊 Totales</a>
          <a href="index.php?page=admin_calendar">🗓️ Calendario</a>
          <a href="index.php?page=admin_schedules">🧭 Horarios</a>
          <a href="index.php?page=admin_adjustments">🛠️ Correcciones
            <?php if ($pendingAdjust>0): ?><span class="badge-dot" title="Correcciones pendientes"><?=$pendingAdjust?></span><?php endif; ?>
          </a>
          <a href="index.php?page=admin_leave_requests">✅ Solicitudes
            <?php if ($pendingLeaves>0): ?><span class="badge-dot" title="Vacaciones pendientes"><?=$pendingLeaves?></span><?php endif; ?>
          </a>
          <a href="index.php?page=admin_leaves">🌴 Vacaciones</a>
          <?php if (($pendingTotal ?? 0) > 0): ?>
            <a href="index.php?page=admin_leave_requests" title="Pendientes totales" style="border-color:#7f1d1d;background:#3b0a0a;color:#fff">
              🔔 Pendientes <span class="badge-dot"><?=$pendingTotal?></span>
            </a>
          <?php endif; ?>
        <?php else: ?>
          <a href="index.php?page=employee">🪪 Empleado</a>
          <a href="index.php?page=employee_month">🗓️ Mi mes</a>
          <a href="index.php?page=employee_totals">📈 Mis horas</a>
          <a href="index.php?page=request_leave">🌴 Vacaciones</a>
        <?php endif; ?>
      <?php endif; ?>
    </nav>
    <div class="who">
      <?php if ($u): ?>
        <?php if ($isEmployee): ?>
          <span class="pill">
            <span class="dot <?= $openNow ? 'green' : 'red' ?>"></span>
            <span><?= $openNow ? 'Dentro' : 'Fuera' ?></span>
            <?php if ($openNow): ?>
              <span class="timer" id="hdrTimer" data-start-utc="<?= h($startUtcIso) ?>">· 00:00:00</span>
            <?php endif; ?>
          </span>
          <?php if ($openNow): ?>
            <span class="small">Inicio: <?= h($startedAtEs) ?> (España)</span>
            <form method="post" action="index.php?page=employee" style="margin-left:.2rem">
              <?php csrf_input(); ?>
              <button class="btn small outline" name="out">Fichar SALIDA</button>
            </form>
          <?php endif; ?>
        <?php else: ?>
          <span class="small">Admin</span>
        <?php endif; ?>
        <span class="small">• <?= h($u['name']) ?></span>
        <a class="small" href="index.php?page=logout">Salir</a>
      <?php else: ?>
        <a class="small" href="index.php?page=login">Entrar</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="container">
    <?php if (function_exists('flash_get') && ($f = flash_get())): ?>
      <div class="card" style="border-left:4px solid <?= $f['type']==='success'?'#22c55e':($f['type']==='danger'?'#ef4444':'#f59e0b') ?>;">
        <?= h($f['message']) ?>
      </div>
    <?php endif; ?>

<?php if ($openNow): ?>
<script>
(function(){
  var el = document.getElementById('hdrTimer');
  if(!el) return;
  var start = new Date(el.dataset.startUtc);
  function pad(n){return (n<10?'0':'')+n;}
  function tick(){
    var now = new Date();
    var diff = Math.max(0, Math.floor((now - start)/1000));
    var h = Math.floor(diff/3600);
    var m = Math.floor((diff%3600)/60);
    var s = diff%60;
    el.textContent = '· ' + pad(h)+':'+pad(m)+':'+pad(s);
  }
  tick(); setInterval(tick, 1000);
})();
</script>
<?php endif; ?>
