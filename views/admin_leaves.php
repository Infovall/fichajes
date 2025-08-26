<?php
// views/admin_leaves.php — Admin: reporte de vacaciones (previstas/disfrutadas/pendientes) + exportación CSV
csrf_check();
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/helpers.php';

require_role('admin');

$y = isset($_GET['y']) ? (int)$_GET['y'] : (int)date('Y');
$dep = isset($_GET['dep']) ? (int)$_GET['dep'] : 0;

$yearStart = sprintf('%04d-01-01', $y);
$yearEnd   = sprintf('%04d-01-01', $y+1);
$today     = date('Y-m-d');

// Carga departamentos para filtro
$deps = db()->query("SELECT id, name FROM " . pfx('departments') . " ORDER BY name")->fetchAll();
$depNames = []; foreach($deps as $d){ $depNames[(int)$d['id']] = $d['name']; }

// Carga usuarios (opcional filtro por departamento)
$sqlUsers = "SELECT u.id, u.name, u.email, u.department_id, COALESCE(d.name,'') dname
             FROM " . pfx('users') . " u
             LEFT JOIN " . pfx('departments') . " d ON d.id=u.department_id";
$params = [];
if ($dep) { $sqlUsers .= " WHERE u.department_id=?"; $params[] = $dep; }
$sqlUsers .= " ORDER BY d.name, u.name";
$users = db()->prepare($sqlUsers); $users->execute($params); $users = $users->fetchAll();

// Helpers de días
function days_in_span($start, $end){
  $s = new DateTime($start); $e = new DateTime($end);
  if ($e < $s) return 0; return $s->diff($e)->days + 1;
}
function days_in_year_cut($start, $end, $yearStart, $yearEnd){
  $s = max($start, $yearStart);
  $e = min($end, date('Y-m-d', strtotime($yearEnd . ' -1 day')));
  return days_in_span($s, $e);
}

// Prepara acumuladores por usuario
$data = []; // [uid] => ['name'=>, 'email'=>, 'dep'=>, 'prev'=>, 'past'=>, 'pend'=>]
$secTotals = []; // [depId] => ['name'=>, 'prev'=>, 'past'=>, 'pend'=>]
foreach ($users as $u) {
  $data[$u['id']] = ['name'=>$u['name'], 'email'=>$u['email'], 'dep'=>$u['dname'], 'prev'=>0, 'past'=>0, 'pend'=>0];
  $did = (int)($u['department_id'] ?? 0);
  if (!isset($secTotals[$did])) { $secTotals[$did] = ['name'=>$depNames[$did] ?? '—', 'prev'=>0, 'past'=>0, 'pend'=>0]; }
}

// Consulta todas las solicitudes del año para los usuarios cargados
if ($users) {
  $ids = array_map(fn($r)=> (int)$r['id'], $users);
  $in  = implode(',', array_fill(0, count($ids), '?'));
  $sql = "SELECT lr.user_id, lr.start, lr.end, lr.status
          FROM " . pfx('leave_requests') . " lr
          WHERE lr.user_id IN ($in) AND lr.start < ? AND lr.end >= ?";
  $stmt = db()->prepare($sql);
  $params2 = $ids; $params2[] = $yearEnd; $params2[] = $yearStart;
  $stmt->execute($params2);
  $rows = $stmt->fetchAll();

  foreach ($rows as $r) {
    $uid = (int)$r['user_id'];
    $did = 0;
    // obtenemos dep id del usuario
    foreach($users as $u){ if ((int)$u['id']===$uid){ $did = (int)($u['department_id'] ?? 0); break; } }
    $spanYear = days_in_year_cut($r['start'], $r['end'], $yearStart, $yearEnd);
    if ($spanYear <= 0) continue;

    if ($r['status']==='approved') {
      if ($r['end'] < $today) { // ya finalizadas
        $data[$uid]['past'] += $spanYear;
        $secTotals[$did]['past'] += $spanYear;
      } else { // en curso/futuras
        $data[$uid]['prev'] += $spanYear;
        $secTotals[$did]['prev'] += $spanYear;
      }
    } elseif ($r['status']==='pending') {
      $data[$uid]['pend'] += $spanYear;
      $secTotals[$did]['pend'] += $spanYear;
    }
  }
}

// Exportación CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="reporte_vacaciones_'.$y.($dep ? '_dep'.$dep : '').'.csv"');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['Año', $y]);
  if ($dep) { fputcsv($out, ['Sección', $depNames[$dep] ?? '']); }
  fputcsv($out, []);
  fputcsv($out, ['Empleado', 'Email', 'Sección', 'Previstas (días)', 'Disfrutadas (días)', 'Pendientes (días)']);
  foreach ($data as $uid=>$r) {
    fputcsv($out, [$r['name'], $r['email'], $r['dep'], (int)$r['prev'], (int)$r['past'], (int)$r['pend']]);
  }
  fputcsv($out, []);
  fputcsv($out, ['Totales por sección']);
  fputcsv($out, ['Sección', 'Previstas', 'Disfrutadas', 'Pendientes']);
  foreach ($secTotals as $did=>$t) {
    fputcsv($out, [$t['name'], (int)$t['prev'], (int)$t['past'], (int)$t['pend']]);
  }
  fclose($out);
  exit;
}

?>
<h1>Vacaciones — Reporte <?=$y?></h1>

<form method="get" class="card">
  <input type="hidden" name="page" value="admin_leaves">
  <div class="actions" style="gap:.8rem;flex-wrap:wrap">
    <label>Año <input type="number" name="y" value="<?=$y?>" style="max-width:120px"></label>
    <label>Sección
      <select name="dep">
        <option value="0">Todas</option>
        <?php foreach($deps as $d): ?>
          <option value="<?=$d['id']?>" <?=$dep===(int)$d['id']?'selected':''?>><?=h($d['name'])?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <button class="btn">Aplicar</button>
    <a class="btn outline" href="index.php?page=admin_leaves&y=<?=$y?>&dep=<?=$dep?>&export=csv">Exportar CSV</a>
  </div>
</form>

<section class="card">
  <h3>Totales por sección</h3>
  <table>
    <thead><tr><th>Sección</th><th>Previstas</th><th>Disfrutadas</th><th>Pendientes</th></tr></thead>
    <tbody>
      <?php if (!$secTotals): ?>
        <tr><td colspan="4" class="small">No hay datos para el año seleccionado.</td></tr>
      <?php else: foreach($secTotals as $did=>$t): ?>
        <tr>
          <td><?=h($t['name'])?></td>
          <td><?= (int)$t['prev'] ?></td>
          <td><?= (int)$t['past'] ?></td>
          <td><?= (int)$t['pend'] ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</section>

<section class="card">
  <h3>Detalle por empleado</h3>
  <table>
    <thead><tr><th>Empleado</th><th>Email</th><th>Sección</th><th>Previstas</th><th>Disfrutadas</th><th>Pendientes</th></tr></thead>
    <tbody>
      <?php if (!$data): ?>
        <tr><td colspan="6" class="small">No hay empleados para el filtro seleccionado.</td></tr>
      <?php else: foreach($data as $uid=>$r): ?>
        <tr>
          <td><?=h($r['name'])?></td>
          <td class="small"><?=h($r['email'])?></td>
          <td><?=h($r['dep'])?></td>
          <td><?= (int)$r['prev'] ?></td>
          <td><?= (int)$r['past'] ?></td>
          <td><?= (int)$r['pend'] ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</section>
