<?php csrf_check(); require_once __DIR__ . '/../core/db.php'; ?>
<h1>Mis horas del mes</h1>
<?php
$uid = user()['id'];
$y = isset($_GET['y']) ? (int)$_GET['y'] : (int)date('Y');
$m = isset($_GET['m']) ? (int)$_GET['m'] : (int)date('n');
$start = sprintf('%04d-%02d-01 00:00:00', $y, $m);
$end   = date('Y-m-d H:i:s', strtotime($start.' +1 month'));
$q = db()->prepare("SELECT SUM(TIMESTAMPDIFF(MINUTE, clock_in, COALESCE(clock_out, UTC_TIMESTAMP()))) AS mins
                    FROM " . pfx('time_entries') . " WHERE user_id=? AND clock_in >= ? AND clock_in < ?");
$q->execute([$uid,$start,$end]); $mins = (int)($q->fetch()['mins'] ?? 0);
$now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
$today_end = $now->format('Y-m-d H:i:s');
$q2 = db()->prepare("SELECT SUM(TIMESTAMPDIFF(MINUTE, clock_in, COALESCE(clock_out, UTC_TIMESTAMP()))) AS mins
                     FROM " . pfx('time_entries') . " WHERE user_id=? AND clock_in >= ? AND clock_in < ?");
$q2->execute([$uid,$start, min($end, $today_end)]); $mins_to_date = (int)($q2->fetch()['mins'] ?? 0);
function hhmm($mins){ $h=intdiv($mins,60); $m=$mins%60; return sprintf('%02d:%02d',$h,$m); }
$rows = db()->prepare("SELECT id, clock_in, clock_out FROM " . pfx('time_entries') . " WHERE user_id=? AND clock_in >= ? AND clock_in < ? ORDER BY clock_in ASC");
$rows->execute([$uid,$start,$end]); $rows = $rows->fetchAll();
?>
<section class="card">
  <form class="actions" method="get">
    <input type="hidden" name="page" value="employee_month">
    <label>Año <input type="number" name="y" value="<?=$y?>" style="max-width:120px"></label>
    <label>Mes <input type="number" name="m" min="1" max="12" value="<?=$m?>" style="max-width:120px"></label>
    <button class="btn">Ver</button>
    <a class="btn outline" href="index.php?page=employee_totals&y=<?=$y?>&m=<?=$m?>">Ver total</a>
  </form>
  <div class="grid">
    <div class="card"><div class="kpi"><div class="value"><?=hhmm($mins)?></div><div class="hint">Total del mes</div></div></div>
    <div class="card"><div class="kpi"><div class="value"><?=hhmm($mins_to_date)?></div><div class="hint">Acumulado a hoy</div></div></div>
  </div>
</section>
<section class="card">
  <h3>Fichajes</h3>
  <table>
    <thead><tr><th>Entrada (España)</th><th>Salida (España)</th><th>Editar</th></tr></thead>
    <tbody>
      <?php foreach($rows as $r): ?>
        <?php
          $in_es  = $r['clock_in']  ? (new DateTime($r['clock_in'],  new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Europe/Madrid'))->format('Y-m-d H:i:s') : '—';
          $out_es = $r['clock_out'] ? (new DateTime($r['clock_out'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Europe/Madrid'))->format('Y-m-d H:i:s') : '—';
        ?>
        <tr>
          <td><?=h($in_es)?></td>
          <td><?=h($out_es)?></td>
          <td><a class="btn outline" href="index.php?page=request_edit&id=<?=$r['id']?>">Corregir</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>
