<?php
// views/admin_schedules.php — CRUD + asignación
// FIX: sin header('Location') dentro de la vista (ya hay output por header.php).
// En su lugar, mostramos mensaje y recargamos con JS/Meta (PRG-like).

declare(strict_types=1);
csrf_check();
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/helpers.php';
require_role('admin');

$err = null;
$info = null;
$autoReload = false;
$reloadUrl = 'index.php?page=admin_schedules';

// ===== Acciones =====
if ($_SERVER['REQUEST_METHOD']==='POST') {
  try {
    // Crear horario
    if (isset($_POST['create'])) {
      $name = trim($_POST['name'] ?? '');
      $wh   = (float)($_POST['weekly_hours'] ?? 40);
      $color= trim($_POST['color'] ?? '#7c9bff');
      if ($name==='') throw new Exception('El nombre es obligatorio');
      if ($wh<=0) throw new Exception('Horas semanales debe ser > 0');
      $stmt = db()->prepare("INSERT INTO " . pfx('schedules') . " (name, weekly_hours, color) VALUES (?,?,?)");
      $stmt->execute([$name, $wh, $color]);
      $info = 'Horario creado correctamente';
      $autoReload = true;
    }

    // Actualizar horario
    if (isset($_POST['update'])) {
      $id   = (int)($_POST['id'] ?? 0);
      $name = trim($_POST['name'] ?? '');
      $wh   = (float)($_POST['weekly_hours'] ?? 40);
      $color= trim($_POST['color'] ?? '#7c9bff');
      if ($id<=0) throw new Exception('ID inválido');
      if ($name==='') throw new Exception('El nombre es obligatorio');
      if ($wh<=0) throw new Exception('Horas semanales debe ser > 0');
      $stmt = db()->prepare("UPDATE " . pfx('schedules') . " SET name=?, weekly_hours=?, color=? WHERE id=?");
      $stmt->execute([$name, $wh, $color, $id]);
      $info = 'Horario actualizado';
      $autoReload = true;
    }

    // Borrar horario
    if (isset($_POST['delete'])) {
      $id = (int)($_POST['id'] ?? 0);
      if ($id<=0) throw new Exception('ID inválido');
      try { db()->prepare("UPDATE " . pfx('users') . " SET schedule_id=NULL WHERE schedule_id=?")->execute([$id]); } catch (Throwable $e) {}
      db()->prepare("DELETE FROM " . pfx('schedules') . " WHERE id=?")->execute([$id]);
      $info = 'Horario eliminado';
      $autoReload = true;
    }

    // Asignar horario a un usuario
    if (isset($_POST['assign'])) {
      $uid = (int)($_POST['user_id'] ?? 0);
      $sid = isset($_POST['schedule_id']) && $_POST['schedule_id']!=='' ? (int)$_POST['schedule_id'] : null;
      if ($uid<=0) throw new Exception('Usuario inválido');
      $sql = "UPDATE " . pfx('users') . " SET schedule_id = :sid WHERE id = :uid";
      $st = db()->prepare($sql);
      if ($sid === null) { $st->bindValue(':sid', null, PDO::PARAM_NULL); } else { $st->bindValue(':sid', $sid, PDO::PARAM_INT); }
      $st->bindValue(':uid', $uid, PDO::PARAM_INT);
      $st->execute();
      $info = 'Asignación actualizada';
      $autoReload = true;
    }

  } catch (Throwable $ex) {
    $err = $ex->getMessage();
    $autoReload = false;
  }
}

// ===== Carga de datos =====
$schedules = []; $schemaOk = true; $schemaErr = null;
try {
  $schedules = db()->query("SELECT id, name, weekly_hours, color, created_at, updated_at FROM " . pfx('schedules') . " ORDER BY name")->fetchAll();
} catch (Throwable $e) { $schemaOk=false; $schemaErr=$e->getMessage(); }

$users = []; $userErr = null; $hasScheduleCol = true;
try { $users = db()->query("SELECT id, name, email, schedule_id FROM " . pfx('users') . " ORDER BY name")->fetchAll(); }
catch (Throwable $e) { $userErr=$e->getMessage(); $hasScheduleCol=false; }

// Mapa id->color para badge en asignación
$mapColor = []; foreach ($schedules as $s) { $mapColor[(int)$s['id']] = $s['color'] ?? '#7c9bff'; }

?>
<h1>Tipos de horario</h1>

<?php if ($info): ?>
  <div class="card" style="border-left:4px solid #22c55e">
    <?= h($info) ?>. <span class="small">Recargando…</span>
  </div>
  <script>setTimeout(function(){ location.href = '<?= h($reloadUrl) ?>'; }, 400);</script>
  <noscript><meta http-equiv="refresh" content="1;url=<?= h($reloadUrl) ?>"></noscript>
<?php endif; ?>

<?php if ($schemaOk === false): ?>
  <div class="card" style="border-left:4px solid #ef4444">
    <strong>Falta la tabla de horarios.</strong><br>
    Error: <?= h($schemaErr) ?><br><br>
    <pre style="white-space:pre-wrap;background:#0b1430;color:#cbd5e1;padding:.6rem;border-radius:8px;border:1px solid #233047">
CREATE TABLE IF NOT EXISTS `<?= h(pfx('schedules')) ?>` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `weekly_hours` DECIMAL(5,2) NOT NULL DEFAULT 40.00,
  `color` VARCHAR(7) DEFAULT '#7c9bff',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;</pre>
  </div>
<?php endif; ?>

<?php if ($userErr): ?>
  <div class="card" style="border-left:4px solid #ef4444">
    <strong>Atención:</strong> No puedo cargar usuarios con <code>schedule_id</code>.<br>
    Error: <?= h($userErr) ?><br><br>
    <pre style="white-space:pre-wrap;background:#0b1430;color:#cbd5e1;padding:.6rem;border-radius:8px;border:1px solid #233047">
ALTER TABLE `<?= h(pfx('users')) ?>`
  ADD COLUMN `schedule_id` INT UNSIGNED NULL DEFAULT NULL,
  ADD INDEX `idx_users_schedule` (`schedule_id`);
ALTER TABLE `<?= h(pfx('users')) ?>`
  ADD CONSTRAINT `fk_users_schedule` FOREIGN KEY (`schedule_id`)
  REFERENCES `<?= h(pfx('schedules')) ?>`(`id`)
  ON DELETE SET NULL ON UPDATE CASCADE;</pre>
  </div>
<?php endif; ?>

<?php if ($err): ?>
  <div class="card" style="border-left:4px solid #ef4444"><strong>Error:</strong> <?= h($err) ?></div>
<?php endif; ?>

<?php
// --- Resto de la vista: reutilizamos la versión "nombre con color" y asignación con badge ---
// Para brevedad, incluimos directamente el HTML/JS de esa versión aquí.
?>

<div class="card">
  <h3>Crear nuevo horario</h3>
  <form method="post" class="actions" style="gap:.6rem;flex-wrap:wrap;align-items:center">
    <?php csrf_input(); ?>
    <input type="hidden" name="create" value="1">
    <label>Nombre
      <input id="new_name" type="text" name="name" required placeholder="p.ej., Jornada completa 40h">
    </label>
    <label>Horas/semana
      <input type="number" step="0.25" min="1" name="weekly_hours" value="40">
    </label>
    <label>Color
      <input id="new_color" type="color" name="color" value="#7c9bff" title="Elige un color">
    </label>
    <button class="btn" style="background:#e5e7eb;color:#111827;border:1px solid #374151">Crear</button>
  </form>
  <p class="small">El texto del <b>Nombre</b> se pinta con el color elegido para que lo veas al instante.</p>
</div>

<section class="card">
  <h3>Horarios existentes</h3>
  <table>
    <thead>
      <tr>
        <th>Nombre</th>
        <th>Horas/semana</th>
        <th>Color</th>
        <th>Creado</th>
        <th>Actualizado</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$schedules): ?>
        <tr><td colspan="6" class="small">No hay horarios todavía.</td></tr>
      <?php else: foreach ($schedules as $s): $cid = 'c'.(int)$s['id']; $nid='n'.(int)$s['id']; $col = $s['color'] ?? '#7c9bff'; ?>
        <tr>
          <form method="post" class="actions" style="gap:.5rem;align-items:center">
            <?php csrf_input(); ?>
            <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
            <td><input id="<?= h($nid) ?>" type="text" name="name" value="<?= h($s['name']) ?>" required style="color: <?= h($col) ?>;"></td>
            <td><input type="number" step="0.25" min="1" name="weekly_hours" value="<?= h((string)$s['weekly_hours']) ?>"></td>
            <td>
              <input id="<?= h($cid) ?>" type="color" name="color" value="<?= h($col) ?>" title="Elige un color" oninput="document.getElementById('<?= h($nid) ?>').style.color=this.value;">
            </td>
            <td class="small"><?= h((string)$s['created_at']) ?></td>
            <td class="small"><?= h((string)$s['updated_at']) ?></td>
            <td style="display:flex;gap:.4rem;flex-wrap:wrap">
              <button class="btn small" name="update" value="1" style="background:#22c55e;color:#0b1220;border:1px solid #14532d">Guardar</button>
              <button class="btn small" name="delete" value="1" style="background:#fecaca;color:#111827;border:1px solid #7f1d1d" onclick="return confirm('¿Eliminar este horario? Se desasignará de los usuarios que lo tengan.')">Borrar</button>
            </td>
          </form>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</section>

<section class="card">
  <h3>Asignación de horario por usuario</h3>
  <table>
    <thead><tr><th>Empleado</th><th>Email</th><th>Horario</th><th>Color</th><th>Acción</th></tr></thead>
    <tbody>
      <?php if (!$hasScheduleCol): ?>
        <tr><td colspan="5" class="small">Falta la columna <code>schedule_id</code> en usuarios. Usa el SQL de arriba.</td></tr>
      <?php elseif (!$users): ?>
        <tr><td colspan="5" class="small">No hay usuarios o no se pueden cargar.</td></tr>
      <?php else: foreach ($users as $u): $cur=(int)($u['schedule_id'] ?? 0); $curColor = $mapColor[$cur] ?? '#7c9bff'; $dot='d'.$u['id']; ?>
        <tr>
          <form method="post" class="actions" style="gap:.6rem;align-items:center">
            <?php csrf_input(); ?>
            <input type="hidden" name="assign" value="1">
            <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
            <td><?= h($u['name']) ?></td>
            <td class="small"><?= h($u['email']) ?></td>
            <td>
              <select name="schedule_id" onchange="var col=this.selectedOptions[0].dataset.col||'#7c9bff'; var dot=document.getElementById('<?= h($dot) ?>'); if(dot) dot.style.backgroundColor=col;">
                <option value="" data-col="#7c9bff" <?= $cur? '' : 'selected' ?>>— Sin horario —</option>
                <?php foreach ($schedules as $s): $c=$s['color'] ?? '#7c9bff'; ?>
                  <option value="<?= (int)$s['id'] ?>" data-col="<?= h($c) ?>" <?= $cur===(int)$s['id'] ? 'selected' : '' ?>>
                    <?= h($s['name']) ?> (<?= h((string)$s['weekly_hours']) ?>h)
                  </option>
                <?php endforeach; ?>
              </select>
            </td>
            <td><span id="<?= h($dot) ?>" style="display:inline-block;width:14px;height:14px;border-radius:50%;background: <?= h($curColor) ?>;border:1px solid #233047"></span></td>
            <td>
              <button class="btn small" style="background:#93c5fd;color:#111827;border:1px solid #1e3a8a">Guardar asignación</button>
            </td>
          </form>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</section>

<script>
// Pintar el input de nombre del formulario de creación con el color elegido
(function(){
  var color = document.getElementById('new_color');
  var name  = document.getElementById('new_name');
  if(color && name){
    function sync(){ name.style.color = color.value || '#7c9bff'; }
    color.addEventListener('input', sync);
    sync();
  }
})();
</script>
