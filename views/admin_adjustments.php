<?php csrf_check(); require_once __DIR__ . '/../core/db.php'; ?>
<h1>Solicitudes de corrección de fichajes</h1>
<?php
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'], $_POST['id'])){
  $id = (int)$_POST['id']; $act = $_POST['action'];
  $req = db()->prepare("SELECT * FROM " . pfx('time_adjust_requests') . " WHERE id=?"); $req->execute([$id]); $req = $req->fetch();
  if(!$req){ flash_set('danger','Solicitud no encontrada'); redirect('index.php?page=admin_adjustments'); }
  if($act==='reject'){
    db()->prepare("UPDATE " . pfx('time_adjust_requests') . " SET status='rejected', approved_by=?, applied_at=UTC_TIMESTAMP() WHERE id=?")->execute([user()['id'], $id]);
    flash_set('info','Solicitud rechazada'); redirect('index.php?page=admin_adjustments');
  }
  if($act==='approve'){
    $pdo = db(); $pdo->beginTransaction();
    try{
      $entry_id = $req['entry_id'] ? (int)$req['entry_id'] : null;
      $n_in  = $req['new_clock_in'];
      $n_out = $req['new_clock_out'];
      if($entry_id){
        if($n_in){ $pdo->prepare("UPDATE " . pfx('time_entries') . " SET clock_in=? WHERE id=?")->execute([$n_in, $entry_id]); }
        if($n_out){ $pdo->prepare("UPDATE " . pfx('time_entries') . " SET clock_out=? WHERE id=?")->execute([$n_out, $entry_id]); }
      } else {
        if($n_in && $n_out){
          $pdo->prepare("INSERT INTO " . pfx('time_entries') . " (user_id, clock_in, clock_out) VALUES (?,?,?)")->execute([$req['user_id'], $n_in, $n_out]);
        } else { throw new Exception('Para crear una nueva entrada se necesitan entrada y salida'); }
      }
      $pdo->prepare("UPDATE " . pfx('time_adjust_requests') . " SET status='approved', approved_by=?, applied_at=UTC_TIMESTAMP() WHERE id=?")->execute([user()['id'], $id]);
      $pdo->commit(); flash_set('success','Corrección aplicada');
    } catch(Throwable $e){ $pdo->rollBack(); flash_set('danger','Error al aplicar: ' . $e->getMessage()); }
    redirect('index.php?page=admin_adjustments');
  }
}
$st = $_GET['status'] ?? 'pending';
$params=[]; $where=''; if(in_array($st,['pending','approved','rejected'],true)){ $where=" WHERE r.status=? "; $params[]=$st; }
$sql = "SELECT r.*, u.name uname, u.email uemail FROM " . pfx('time_adjust_requests') . " r JOIN " . pfx('users') . " u ON u.id=r.user_id $where ORDER BY r.created_at DESC LIMIT 300";
$stmt = db()->prepare($sql); $stmt->execute($params); $rows = $stmt->fetchAll();
?>
<form method="get" class="card">
  <input type="hidden" name="page" value="admin_adjustments">
  <label>Estado
    <select name="status">
      <option value="">Todos</option>
      <option value="pending"  <?=$st==='pending'?'selected':''?>>Pendiente</option>
      <option value="approved" <?=$st==='approved'?'selected':''?>>Aprobado</option>
      <option value="rejected" <?=$st==='rejected'?'selected':''?>>Rechazado</option>
    </select>
  </label>
  <button class="btn">Filtrar</button>
</form>
<section class="card">
  <table>
    <thead><tr><th>Empleado</th><th>Entrada actual</th><th>Salida actual</th><th>Nueva entrada</th><th>Nueva salida</th><th>Motivo</th><th>Estado</th><th>Acción</th></tr></thead>
    <tbody>
      <?php foreach($rows as $r):
        $curr=['in'=>null,'out'=>null];
        if($r['entry_id']){ $e=db()->prepare("SELECT clock_in, clock_out FROM " . pfx('time_entries') . " WHERE id=?"); $e->execute([$r['entry_id']]); $curr=$e->fetch() ?: $curr; }
      ?>
      <tr>
        <td><?=h($r['uname'])?><br><span class="small"><?=h($r['uemail'])?></span></td>
        <td><?=h($curr['clock_in'] ?? '—')?></td>
        <td><?=h($curr['clock_out'] ?? '—')?></td>
        <td><?=h($r['new_clock_in'] ?: '—')?></td>
        <td><?=h($r['new_clock_out'] ?: '—')?></td>
        <td><?=h($r['reason'] ?: '')?></td>
        <td><?=h($r['status'])?></td>
        <td class="actions">
          <?php if($r['status']==='pending'): ?>
            <form method="post" style="display:inline"><?php csrf_input(); ?><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn" name="action" value="approve">Aplicar</button></form>
            <form method="post" style="display:inline"><?php csrf_input(); ?><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn outline" name="action" value="reject">Rechazar</button></form>
          <?php else: ?>—<?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>
