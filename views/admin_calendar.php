<?php
// views/admin_calendar.php — Calendario mensual con badge de horario por empleado
declare(strict_types=1);
csrf_check();
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/helpers.php';
require_role('admin');

$tz = new DateTimeZone('Europe/Madrid');
$y = (int)($_GET['y'] ?? (new DateTime('now', $tz))->format('Y'));
$m = (int)($_GET['m'] ?? (new DateTime('now', $tz))->format('m'));
$monthStart = sprintf('%04d-%02d-01', $y, $m);
$monthEnd = (new DateTime($monthStart, $tz))->modify('first day of next month')->format('Y-m-d');
$daysInMonth = (int)(new DateTime($monthStart))->format('t');
$firstDow = (int)(new DateTime($monthStart))->format('N'); // 1..7 (lunes=1)

// Cargar vacaciones aprobadas que toquen el mes + datos de usuario y horario
$leaves = []; $err = null; $scheds = [];
try {
  $sql = "SELECT lr.id, lr.user_id, lr.start, lr.end,
                 u.name AS uname, u.department_id, u.schedule_id,
                 s.name AS sname, s.color AS scolor
          FROM " . pfx('leave_requests') . " lr
          JOIN " . pfx('users') . " u ON u.id = lr.user_id
          LEFT JOIN " . pfx('schedules') . " s ON s.id = u.schedule_id
          WHERE lr.status='approved' AND lr.start < ? AND lr.end >= ?
          ORDER BY lr.start ASC";
  $st = db()->prepare($sql);
  $st->execute([$monthEnd, $monthStart]);
  $leaves = $st->fetchAll();
} catch (Throwable $e) { $err = $e->getMessage(); }

// Indexar por día
$byDay = []; for ($d=1; $d<=$daysInMonth; $d++) { $byDay[$d] = []; }
foreach ($leaves as $r) {
  $s = new DateTime($r['start']); $e = new DateTime($r['end']);
  $last = new DateTime($monthEnd . ' -1 day');
  $spanStart = max($s, new DateTime($monthStart));
  $spanEnd   = min($e, $last);
  for ($dt = clone $spanStart; $dt <= $spanEnd; $dt->modify('+1 day')) {
    $dayNum = (int)$dt->format('j');
    if ($dayNum>=1 && $dayNum<=$daysInMonth) { $byDay[$dayNum][] = $r; }
  }
}

// Extraer leyenda de horarios presentes en el mes
$legend = [];
foreach ($leaves as $r) {
  if (!empty($r['sname'])) {
    $legend[$r['sname']] = $r['scolor'] ?? '#7c9bff';
  }
}
?>
<h1>Calendario <?=$y?>-<?=sprintf('%02d',$m)?></h1>

<style>
  .cal{display:grid;grid-template-columns:repeat(7,1fr);gap:.5rem}
  .cal .cell{min-height:120px;border:1px solid #1f2937;border-radius:12px;padding:.4rem;background:#111827}
  .cal .head{font-weight:600;color:#cbd5e1;text-align:center}
  .tag{display:flex;align-items:center;gap:.35rem;margin:.2rem 0;padding:.1rem .35rem;border-radius:999px;font-size:.75rem;border:1px solid #334155}
  .dot{width:.7rem;height:.7rem;border-radius:999px;border:1px solid #233047}
</style>

<div class="card">
  <form method="get" class="actions" style="gap:.6rem;flex-wrap:wrap">
    <input type="hidden" name="page" value="admin_calendar">
    <label>Año <input type="number" name="y" value="<?=$y?>" style="max-width:120px"></label>
    <label>Mes <input type="number" name="m" min="1" max="12" value="<?=$m?>" style="max-width:120px"></label>
    <button class="btn" style="background:#e5e7eb;color:#111827;border:1px solid #374151">Cambiar</button>
  </form>
</div>

<?php if ($err): ?>
  <div class="card" style="border-left:4px solid #ef4444"><strong>Error:</strong> <?= h($err) ?></div>
<?php endif; ?>

<section class="card">
  <div class="cal">
    <?php
      $wdNames = ['L','M','X','J','V','S','D'];
      foreach ($wdNames as $n) { echo '<div class="head">',$n,'</div>'; }
      for ($i=1; $i<$firstDow; $i++) echo '<div class="cell small" style="opacity:.4"></div>';
      for ($day=1; $day<=$daysInMonth; $day++):
    ?>
      <div class="cell">
        <div class="small" style="color:#9ca3af">día <?=$day?></div>
        <?php if (empty($byDay[$day])): ?>
          <div class="small" style="opacity:.6">—</div>
        <?php else: foreach ($byDay[$day] as $r):
          $name = h($r['uname']);
          $sname = $r['sname'] ? h($r['sname']) : '';
          $scol = $r['scolor'] ?? '#7c9bff';
        ?>
          <div class="tag">
            <?php if ($sname): ?><span class="dot" style="background: <?= h($scol) ?>"></span><?php endif; ?>
            <span><?= $name ?></span>
            <?php if ($sname): ?><span class="small" style="opacity:.8">· <?= $sname ?></span><?php endif; ?>
          </div>
        <?php endforeach; endif; ?>
      </div>
    <?php endfor; ?>
  </div>
</section>

<?php if ($legend): ?>
<section class="card">
  <h3>Horarios presentes este mes</h3>
  <div class="actions" style="flex-wrap:wrap">
    <?php foreach ($legend as $n=>$c): ?>
      <span class="tag"><span class="dot" style="background: <?= h($c) ?>"></span><?= h($n) ?></span>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
