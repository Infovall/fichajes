<?php csrf_check(); require_once __DIR__ . '/../core/db.php'; ?>
<h1>Totales del mes por empleado</h1>
<?php
$y = isset($_GET['y']) ? (int)$_GET['y'] : (int)date('Y');
$m = isset($_GET['m']) ? (int)$_GET['m'] : (int)date('n');
$start = sprintf('%04d-%02d-01 00:00:00', $y, $m);
$end   = date('Y-m-d H:i:s', strtotime($start.' +1 month'));
function mins_for($uid,$start,$end){
  $q = db()->prepare("SELECT SUM(TIMESTAMPDIFF(MINUTE, clock_in, COALESCE(clock_out, UTC_TIMESTAMP()))) AS mins
                      FROM " . pfx('time_entries') . " WHERE user_id=? AND clock_in >= ? AND clock_in < ?");
  $q->execute([$uid,$start,$end]); return (int)($q->fetch()['mins'] ?? 0);
}
function hhmm($mins){ $h=intdiv($mins,60); $m=$mins%60; return sprintf('%02d:%02d',$h,$m); }
$users = db()->query("SELECT u.id, u.name, u.email, COALESCE(d.name,'') dname FROM " . pfx('users') . " u LEFT JOIN " . pfx('departments') . " d ON d.id=u.department_id ORDER BY d.name, u.name")->fetchAll();
?>
<form method="get" class="card">
  <input type="hidden" name="page" value="admin_totals">
  <label>Año <input type="number" name="y" value="<?=$y?>" style="max-width:120px"></label>
  <label>Mes <input type="number" name="m" min="1" max="12" value="<?=$m?>" style="max-width:120px"></label>
  <button class="btn">Ver</button>
</form>
<section class="card">
  <table>
    <thead><tr><th>Empleado</th><th>Sección</th><th>Total mes</th></tr></thead>
    <tbody>
      <?php foreach($users as $u): $mins = mins_for($u['id'],$start,$end); ?>
        <tr><td><?=h($u['name'])?></td><td><?=h($u['dname'])?></td><td><?=hhmm($mins)?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>
