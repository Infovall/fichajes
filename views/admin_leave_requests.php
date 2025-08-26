<?php
// views/admin_leave_requests.php — Admin: aprobar/rechazar solicitudes
// FIX PRG-like: sin header('Location'), mostramos mensaje y recargamos con JS/meta.
declare(strict_types=1);
csrf_check();
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/helpers.php';
require_role('admin');

$y = isset($_GET['y']) ? (int)$_GET['y'] : (int)date('Y');
$filter = $_GET['f'] ?? 'pending'; // pending | all
$yearStart = sprintf('%04d-01-01', $y);
$yearEnd   = sprintf('%04d-01-01', $y+1);

$info = null;
$err  = null;
$reloadUrl = 'index.php?page=admin_leave_requests&y='.$y.'&f='.$filter;

// Acciones (aprobar/rechazar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['action'])) {
  try {
    $id = (int)$_POST['id'];
    $act = $_POST['action'] === 'approve' ? 'approved' : 'rejected';
    $stmt = db()->prepare("UPDATE " . pfx('leave_requests') . " SET status=? WHERE id=?");
    $stmt->execute([$act, $id]);
    $info = 'Solicitud #'.$id.' → '.($act==='approved'?'APROBADA':'RECHAZADA');
  } catch (Throwable $e) {
    $err = $e->getMessage();
  }
}

// Cargar solicitudes
$sql = "SELECT lr.id, lr.user_id, lr.start, lr.end, lr.reason, lr.status,
               u.name AS uname, u.email, COALESCE(d.name,'') AS dname
        FROM " . pfx('leave_requests') . " lr
        JOIN " . pfx('users') . " u ON u.id = lr.user_id
        LEFT JOIN " . pfx('departments') . " d ON d.id = u.department_id
        WHERE lr.start < ? AND lr.end >= ?";
$params = [$yearEnd, $yearStart];
if ($filter === 'pending') {
  $sql .= " AND lr.status='pending'";
}
$sql .= " ORDER BY (lr.status='pending') DESC, lr.start DESC, lr.id DESC";
$rows = db()->prepare($sql);
$rows->execute($params);
$rows = $rows->fetchAll();
?>
<h1>Solicitudes de vacaciones — <?=$y?></h1>

<?php if ($info): ?>
  <div class="card" style="border-left:4px solid #22c55e">
    <?= h($info) ?>. <span class="small">Recargando…</span>
  </div>
  <script>setTimeout(function(){ location.href = '<?= h($reloadUrl) ?>'; }, 400);</script>
  <noscript><meta http-equiv="refresh" content="1;url=<?= h($reloadUrl) ?>"></noscript>
<?php endif; ?>

<form method="get" class="card" style="margin-bottom:1rem">
  <input type="hidden" name="page" value="admin_leave_requests">
  <div class="actions" style="gap:.8rem;flex-wrap:wrap">
    <label>Año <input type="number" name="y" value="<?=$y?>" style="max-width:120px"></label>
    <label>Filtro
      <select name="f">
        <option value="pending" <?=$filter==='pending'?'selected':''?>>Solo pendientes</option>
        <option value="all" <?=$filter==='all'?'selected':''?>>Todas</option>
      </select>
    </label>
    <button class="btn" style="background:#e5e7eb;color:#111827;border:1px solid #374151">Aplicar</button>
  </div>
</form>

<?php if ($err): ?>
  <div class="card" style="border-left:4px solid #ef4444"><strong>Error:</strong> <?= h($err) ?></div>
<?php endif; ?>

<section class="card">
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Empleado</th>
        <th>Sección</th>
        <th>Inicio</th>
        <th>Fin</th>
        <th>Días</th>
        <th>Motivo</th>
        <th>Estado</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="9" class="small">No hay solicitudes para el filtro seleccionado.</td></tr>
      <?php else: foreach ($rows as $r): ?>
        <?php
          $s = new DateTime($r['start']); $e = new DateTime($r['end']);
          $days = max(0, $s->diff($e)->days + 1);
          $isPending = $r['status']==='pending';
        ?>
        <tr>
          <td><?=$r['id']?></td>
          <td><?=h($r['uname'])?><br><span class="small"><?=h($r['email'])?></span></td>
          <td><?=h($r['dname'])?></td>
          <td><?=h($r['start'])?></td>
          <td><?=h($r['end'])?></td>
          <td><?=$days?></td>
          <td><?=h($r['reason'] ?? '')?></td>
          <td>
            <?php if ($r['status']==='approved'): ?>
              <span class="badge">Aprobada</span>
            <?php elseif ($r['status']==='rejected'): ?>
              <span class="badge" style="background:#fecaca;border-color:#7f1d1d;color:#111827">Rechazada</span>
            <?php else: ?>
              <span class="badge" style="background:#172554;border-color:#1e3a8a;color:#c7d2fe">Pendiente</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($isPending): ?>
              <form method="post" style="display:inline">
                <?php csrf_input(); ?>
                <input type="hidden" name="id" value="<?=$r['id']?>">
                <input type="hidden" name="action" value="approve">
                <button class="btn small" style="background:#22c55e;color:#0b1220;border:1px solid #14532d" onclick="return confirm('¿Aprobar la solicitud #<?=$r['id']?> de <?=h($r['uname'])?>?')">Aprobar</button>
              </form>
              <form method="post" style="display:inline">
                <?php csrf_input(); ?>
                <input type="hidden" name="id" value="<?=$r['id']?>">
                <input type="hidden" name="action" value="reject">
                <button class="btn small" style="background:#fecaca;color:#111827;border:1px solid #7f1d1d" onclick="return confirm('¿Rechazar la solicitud #<?=$r['id']?> de <?=h($r['uname'])?>?')">Rechazar</button>
              </form>
            <?php else: ?>
              <span class="small muted">—</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</section>
