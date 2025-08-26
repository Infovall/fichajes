<?php csrf_check(); require_once __DIR__ . '/../core/db.php'; ?>
<h1>Mis horas acumuladas</h1>
<?php
$uid = user()['id'];
$y = isset($_GET['y']) ? (int)$_GET['y'] : (int)date('Y');
$m = isset($_GET['m']) ? (int)$_GET['m'] : (int)date('n');
$start = sprintf('%04d-%02d-01 00:00:00', $y, $m);
$end   = date('Y-m-d H:i:s', strtotime($start.' +1 month'));
$q = db()->prepare("SELECT SUM(TIMESTAMPDIFF(MINUTE, clock_in, COALESCE(clock_out, UTC_TIMESTAMP()))) AS mins
                    FROM " . pfx('time_entries') . " WHERE user_id=? AND clock_in >= ? AND clock_in < ?");
$q->execute([$uid,$start,$end]); $mins = (int)($q->fetch()['mins'] ?? 0);
function hhmm($mins){ $h=intdiv($mins,60); $m=$mins%60; return sprintf('%02d:%02d',$h,$m); }
?>
<form method="get" class="card">
  <input type="hidden" name="page" value="employee_totals">
  <label>Año <input type="number" name="y" value="<?=$y?>" style="max-width:120px"></label>
  <label>Mes <input type="number" name="m" min="1" max="12" value="<?=$m?>" style="max-width:120px"></label>
  <button class="btn">Ver</button>
  <a class="btn outline" href="index.php?page=employee_month&y=<?=$y?>&m=<?=$m?>">Ver detalle</a>
</form>
<section class="grid">
  <div class="card"><div class="kpi"><div class="value"><?=hhmm($mins)?></div><div class="hint">Total acumulado en el mes</div></div></div>
</section>
