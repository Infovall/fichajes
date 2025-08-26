<?php csrf_check(); require_once __DIR__ . '/../core/db.php'; ?>
<h1>Solicitar corrección de fichaje</h1>
<?php
$uid = user()['id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$entry = null;
if($id){
  $stmt = db()->prepare("SELECT id, clock_in, clock_out FROM " . pfx('time_entries') . " WHERE id=? AND user_id=?");
  $stmt->execute([$id,$uid]); $entry = $stmt->fetch();
}
if($_SERVER['REQUEST_METHOD']==='POST'){
  $id = (int)($_POST['id'] ?? 0);
  $n_in = $_POST['new_clock_in'] ?: null;
  $n_out = $_POST['new_clock_out'] ?: null;
  $reason = trim($_POST['reason'] ?? '');
  if(!$id || (!$n_in && !$n_out)){ flash_set('danger','Falta fecha/hora'); }
  else {
    db()->prepare("INSERT INTO " . pfx('time_adjust_requests') . " (user_id, entry_id, new_clock_in, new_clock_out, reason) VALUES (?,?,?,?,?)")
      ->execute([$uid,$id,$n_in,$n_out,$reason]);
    flash_set('success','Solicitud enviada'); redirect('index.php?page=employee_month');
  }
}
?>
<form method="post" class="card" style="max-width:680px">
  <?php csrf_input(); ?>
  <input type="hidden" name="id" value="<?=$entry['id'] ?? 0?>">
  <p class="small">Introduce solo los campos que quieras corregir. Formato <code>YYYY-MM-DD HH:MM:SS</code> (UTC).</p>
  <label>Nueva entrada <input name="new_clock_in" placeholder="2025-08-16 08:00:00"></label>
  <label>Nueva salida <input name="new_clock_out" placeholder="2025-08-16 16:00:00"></label>
  <label>Motivo (opcional) <input name="reason" placeholder="Olvido de fichar"></label>
  <div class="actions">
    <button class="btn">Enviar</button>
    <a class="btn outline" href="index.php?page=employee_month">Cancelar</a>
  </div>
</form>
