<?php
// views/request_leave.php — Empleado: solicitar vacaciones
// FIX PRG-like: sin header('Location'), mostramos mensaje y recargamos con JS/meta.
declare(strict_types=1);
csrf_check();
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/helpers.php';
require_role('employee');

$uid = user()['id'];
$tz = new DateTimeZone('Europe/Madrid');
$y = isset($_GET['y']) ? (int)$_GET['y'] : (int)(new DateTime('now',$tz))->format('Y');
$yearStart = sprintf('%04d-01-01', $y);
$yearEnd   = sprintf('%04d-01-01', $y+1);
$today     = (new DateTime('now',$tz))->format('Y-m-d');

$info = null;
$err  = null;
$reloadUrl = 'index.php?page=request_leave&y='.$y;

// Crear solicitud
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['create'])) {
  try {
    $start = trim($_POST['start'] ?? '');
    $end   = trim($_POST['end'] ?? '');
    $reason= trim($_POST['reason'] ?? '');
    if (!$start || !$end) throw new Exception('Debes indicar fecha de inicio y fin.');
    if ($end < $start) throw new Exception('El fin no puede ser anterior al inicio.');
    $st = db()->prepare("INSERT INTO " . pfx('leave_requests') . " (user_id,start,`end`,reason,status,created_at) VALUES (?,?,?,?, 'pending', NOW())");
    $st->execute([$uid, $start, $end, $reason]);
    $info = 'Solicitud enviada correctamente';
  } catch (Throwable $e) { $err = $e->getMessage(); }
}

// Consultas
$rows = db()->prepare("SELECT id,start,`end`,reason,status FROM " . pfx('leave_requests') . " WHERE user_id=? AND start < ? AND `end` >= ? ORDER BY start DESC");
$rows->execute([$uid, $yearEnd, $yearStart]);
$rows = $rows->fetchAll();

// Resúmenes
$prev = $past = $pend = 0;
foreach ($rows as $r) {
  $s = new DateTime($r['start']); $e = new DateTime($r['end']);
  $spanStart = max($s, new DateTime($yearStart));
  $spanEnd   = min($e, new DateTime($yearEnd.' -1 day'));
  $days = max(0, $spanStart->diff($spanEnd)->days + 1);
  if ($r['status']==='approved') {
    if ($r['end'] < $today) $past += $days; else $prev += $days;
  } elseif ($r['status']==='pending') $pend += $days;
}
?>
<h1>Vacaciones (Empleado)</h1>

<?php if ($info): ?>
  <div class="card" style="border-left:4px solid #22c55e">
    <?= h($info) ?>. <span class="small">Recargando…</span>
  </div>
  <script>setTimeout(function(){ location.href = '<?= h($reloadUrl) ?>'; }, 400);</script>
  <noscript><meta http-equiv="refresh" content="1;url=<?= h($reloadUrl) ?>"></noscript>
<?php endif; ?>

<div class="card">
  <form method="post" class="actions" style="gap:.6rem;flex-wrap:wrap">
    <?php csrf_input(); ?>
    <input type="hidden" name="create" value="1">
    <label>Inicio <input type="date" name="start" required></label>
    <label>Fin <input type="date" name="end" required></label>
    <label>Motivo <input type="text" name="reason" placeholder="Opcional"></label>
    <button class="btn" style="background:#93c5fd;color:#111827;border:1px solid #1e3a8a">Solicitar</button>
  </form>
  <?php if ($err): ?>
    <div class="card" style="border-left:4px solid #ef4444"><strong>Error:</strong> <?= h($err) ?></div>
  <?php endif; ?>
</div>

<section class="grid">
  <div class="card kpi">
    <div class="value"><?= (int)$prev ?></div>
    <div class="hint">Previstas (aprobadas futuras/en curso)</div>
  </div>
  <div class="card kpi">
    <div class="value"><?= (int)$past ?></div>
    <div class="hint">Disfrutadas</div>
  </div>
  <div class="card kpi">
    <div class="value"><?= (int)$pend ?></div>
    <div class="hint">Pendientes</div>
  </div>
</section>

<section class="card">
  <h3>Mis solicitudes — <?=$y?></h3>
  <div class="actions" style="gap:.6rem;flex-wrap:wrap">
    <form method="get">
      <input type="hidden" name="page" value="request_leave">
      <label>Año <input type="number" name="y" value="<?=$y?>" style="max-width:120px"></label>
      <button class="btn" style="background:#e5e7eb;color:#111827;border:1px solid #374151">Cambiar</button>
    </form>
  </div>
  <table>
    <thead><tr><th>Inicio</th><th>Fin</th><th>Días</th><th>Motivo</th><th>Estado</th></tr></thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="5" class="small">No tienes solicitudes en este año.</td></tr>
      <?php else: foreach ($rows as $r): $s=new DateTime($r['start']); $e=new DateTime($r['end']); $days=max(0,$s->diff($e)->days+1); ?>
        <tr>
          <td><?= h($r['start']) ?></td>
          <td><?= h($r['end']) ?></td>
          <td><?= $days ?></td>
          <td class="small"><?= h($r['reason'] ?? '') ?></td>
          <td>
            <?php if ($r['status']==='approved'): ?>
              <span class="badge">Aprobada</span>
            <?php elseif ($r['status']==='rejected'): ?>
              <span class="badge" style="background:#fecaca;border-color:#7f1d1d;color:#111827">Rechazada</span>
            <?php else: ?>
              <span class="badge" style="background:#172554;border-color:#1e3a8a;color:#c7d2fe">Pendiente</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</section>
