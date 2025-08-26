<?php
// views/admin_hours.php — Listado de horas por día con badge de horario (color)
declare(strict_types=1);
csrf_check();
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/helpers.php';
require_role('admin');

$tz = new DateTimeZone('Europe/Madrid');
$y = (int)($_GET['y'] ?? (new DateTime('now', $tz))->format('Y'));
$m = (int)($_GET['m'] ?? (new DateTime('now', $tz))->format('m'));
$d = (int)($_GET['d'] ?? (new DateTime('now', $tz))->format('d'));
$day = sprintf('%04d-%02d-%02d', $y, $m, $d);
$startDayUtc = (new DateTime($day.' 00:00:00', $tz))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
$endDayUtc   = (new DateTime($day.' 23:59:59', $tz))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');

$rows = []; $err = null; $hasSched = true;
try {
  $sql = "SELECT te.id, te.user_id, te.clock_in, te.clock_out,
                 u.name AS uname, u.email, u.schedule_id,
                 s.name AS sname, s.color AS scolor
          FROM " . pfx('time_entries') . " te
          JOIN " . pfx('users') . " u ON u.id = te.user_id
          LEFT JOIN " . pfx('schedules') . " s ON s.id = u.schedule_id
          WHERE (te.clock_in <= ? AND (te.clock_out IS NULL OR te.clock_out >= ?))
          ORDER BY u.name ASC, te.clock_in ASC";
  $st = db()->prepare($sql);
  $st->execute([$endDayUtc, $startDayUtc]);
  $rows = $st->fetchAll();
} catch (Throwable $e) { $err = $e->getMessage(); }

function to_es(?string $utc, DateTimeZone $tz): string {
  if (!$utc) return '—';
  $dt = new DateTime($utc, new DateTimeZone('UTC'));
  return $dt->setTimezone($tz)->format('Y-m-d H:i:s');
}
function human_dur(?string $inUtc, ?string $outUtc): string {
  if (!$inUtc) return '—';
  $start = new DateTime($inUtc, new DateTimeZone('UTC'));
  $end = $outUtc ? new DateTime($outUtc, new DateTimeZone('UTC')) : new DateTime('now', new DateTimeZone('UTC'));
  if ($end < $start) return '—';
  $sec = $end->getTimestamp() - $start->getTimestamp();
  $h = intdiv($sec, 3600); $m = intdiv($sec % 3600, 60); $s = $sec % 60;
  return sprintf('%02d:%02d:%02d', $h, $m, $s);
}
?>
<h1>Horas por día</h1>

<style>
  .sched-dot{display:inline-block;width:.8rem;height:.8rem;border-radius:50%;border:1px solid #233047;margin-right:.35rem;vertical-align:middle}
  .sched-name{font-size:.8rem;opacity:.9}
</style>

<div class="card">
  <form method="get" class="actions" style="gap:.6rem;flex-wrap:wrap">
    <input type="hidden" name="page" value="admin_hours">
    <label>Fecha
      <input type="date" name="date" value="<?=h($day)?>" onchange="const d=this.value.split('-'); if(d.length===3){ location.search='?page=admin_hours&y='+d[0]+'&m='+d[1]+'&d='+d[2]; }">
    </label>
    <span class="small">Zona horaria: España</span>
  </form>
</div>

<?php if ($err): ?>
  <div class="card" style="border-left:4px solid #ef4444"><strong>Error de consulta:</strong> <?= h($err) ?></div>
<?php endif; ?>

<section class="card">
  <h3>Listado (<?= h($day) ?>)</h3>
  <table>
    <thead><tr><th>Empleado</th><th>Entrada (ES)</th><th>Salida (ES)</th><th>Duración</th><th>Estado</th></tr></thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="5" class="small">No hay fichajes para esta fecha.</td></tr>
      <?php else: foreach ($rows as $r): $in_es=to_es($r['clock_in'],$tz); $out_es=to_es($r['clock_out'],$tz); $dur=human_dur($r['clock_in'],$r['clock_out']); $open=is_null($r['clock_out']); $dot=$r['scolor'] ?? '#7c9bff'; ?>
        <tr>
          <td>
            <?= h($r['uname']) ?><br>
            <span class="small"><?= h($r['email']) ?></span><br>
            <?php if (!empty($r['sname'])): ?>
              <span class="sched-name"><span class="sched-dot" style="background:<?= h($dot) ?>"></span><?= h($r['sname']) ?></span>
            <?php endif; ?>
          </td>
          <td><?= h($in_es) ?></td>
          <td><?= h($out_es) ?></td>
          <td><?= h($dur) ?></td>
          <td><?= $open ? '<span class="badge" style="background:#1d391d;border-color:#145214;color:#c9f7c9">Abierta</span>' : '<span class="badge">Cerrada</span>' ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</section>
