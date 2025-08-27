<?php
// views/admin_users.php — Gestión de usuarios (CRUD + asignar horario) con PRG-like y alto contraste
declare(strict_types=1);
csrf_check();
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/helpers.php';
require_role('admin');

$info = null;
$err  = null;
$reloadUrl = 'index.php?page=admin_users';

/* Helpers */
function gen_password(int $len = 12): string {
  $bytes = random_bytes(max(6, $len));
  return substr(bin2hex($bytes), 0, $len);
}
function hash_pwd(string $plain): string {
  return password_hash($plain, PASSWORD_DEFAULT);
}

/* Cargar lista de horarios (puede no existir la tabla; si falla, seguimos) */
$schedules = [];
try {
  $schedules = db()->query("SELECT id,name,color FROM " . pfx('schedules') . " ORDER BY name")->fetchAll();
} catch (Throwable $e) {
  $schedules = []; // la vista sigue funcionando sin horarios
}

/* Acciones */
if ($_SERVER['REQUEST_METHOD']==='POST') {
  try {
    // Crear usuario
    if (isset($_POST['create'])) {
      $name  = trim($_POST['name'] ?? '');
      $email = trim($_POST['email'] ?? '');
      $role  = ($_POST['role'] ?? 'employee') === 'admin' ? 'admin' : 'employee';
      $pwd   = trim($_POST['password'] ?? '');
      $schId = isset($_POST['schedule_id']) && $_POST['schedule_id'] !== '' ? (int)$_POST['schedule_id'] : null;
      if ($name==='' || $email==='') throw new Exception('Nombre y email son obligatorios');
      if ($pwd==='') { $pwd = gen_password(12); $showPwd = $pwd; } else { $showPwd = null; }
      $hash = hash_pwd($pwd);

      // Intento con schedule_id
      $sql = "INSERT INTO ".pfx('users')."(name,email,role,password_hash,active,schedule_id,created_at,updated_at)
              VALUES (?,?,?,?,1,?,NOW(),NOW())";
      try {
        db()->prepare($sql)->execute([$name,$email,$role,$hash,$schId]);
      } catch (Throwable $e) {
        // Fallback sin schedule_id (por si no existe la columna)
        $sql = "INSERT INTO ".pfx('users')."(name,email,role,password_hash,active,created_at,updated_at)
                VALUES (?,?,?,?,1,NOW(),NOW())";
        db()->prepare($sql)->execute([$name,$email,$role,$hash]);
      }
      $info = "Usuario creado: " . h($email) . ($showPwd ? " — Contraseña: " . h($showPwd) : '');
    }

    // Actualizar datos
    if (isset($_POST['update'])) {
      $id    = (int)($_POST['id'] ?? 0);
      $name  = trim($_POST['name'] ?? '');
      $email = trim($_POST['email'] ?? '');
      $role  = ($_POST['role'] ?? 'employee') === 'admin' ? 'admin' : 'employee';
      $schId = isset($_POST['schedule_id']) && $_POST['schedule_id'] !== '' ? (int)$_POST['schedule_id'] : null;
      if ($id<=0) throw new Exception('ID inválido');
      if ($name==='' || $email==='') throw new Exception('Nombre y email son obligatorios');

      // Intento con schedule_id
      $sql = "UPDATE ".pfx('users')." SET name=?, email=?, role=?, schedule_id=?, updated_at=NOW() WHERE id=?";
      try {
        db()->prepare($sql)->execute([$name,$email,$role,$schId,$id]);
      } catch (Throwable $e) {
        // Fallback sin schedule_id
        $sql = "UPDATE ".pfx('users')." SET name=?, email=?, role=?, updated_at=NOW() WHERE id=?";
        db()->prepare($sql)->execute([$name,$email,$role,$id]);
      }
      $info = "Usuario actualizado: " . h($email);
    }

    // Activar/desactivar
    if (isset($_POST['toggle'])) {
      $id = (int)($_POST['id'] ?? 0);
      $active = (int)($_POST['active'] ?? 0) ? 1 : 0;
      if ($id<=0) throw new Exception('ID inválido');
      db()->prepare("UPDATE ".pfx('users')." SET active=?, updated_at=NOW() WHERE id=?")->execute([$active,$id]);
      $info = 'Estado actualizado (ID ' . (int)$id . '): ' . ($active ? 'activo' : 'inactivo');
    }

    // Reset pass
    if (isset($_POST['resetpwd'])) {
      $id = (int)($_POST['id'] ?? 0);
      if ($id<=0) throw new Exception('ID inválido');
      $new = gen_password(12);
      $hash = hash_pwd($new);
      db()->prepare("UPDATE ".pfx('users')." SET password_hash=?, updated_at=NOW() WHERE id=?")->execute([$hash,$id]);
      $info = 'Contraseña reestablecida para ID ' . (int)$id . ' — Nueva: ' . h($new);
    }

  } catch (Throwable $e) {
    $err = $e->getMessage();
  }
}

/* Carga */
$users = db()->query("SELECT id,name,email,role,active,IFNULL(schedule_id,0) AS schedule_id FROM ".pfx('users')." ORDER BY role='admin' DESC, name ASC")->fetchAll();

/* Mapa de horarios para selects */
$schMap = [];
foreach ($schedules as $s) { $schMap[(int)$s['id']] = $s['name']; }
?>
<h1>Usuarios</h1>

<?php if ($info): ?>
  <div class="card" style="border-left:4px solid #22c55e"><?= $info ?> <span class="small">Recargando…</span></div>
  <script>setTimeout(()=>location.href='<?= $reloadUrl ?>', 600)</script>
  <noscript><meta http-equiv="refresh" content="1;url=<?= $reloadUrl ?>"></noscript>
<?php endif; ?>
<?php if ($err): ?>
  <div class="card" style="border-left:4px solid #ef4444"><strong>Error:</strong> <?= h($err) ?></div>
<?php endif; ?>

<div class="card">
  <h3>Crear usuario</h3>
  <form method="post" class="actions" style="gap:.6rem;flex-wrap:wrap;align-items:center">
    <?php csrf_input(); ?>
    <input type="hidden" name="create" value="1">
    <label>Nombre <input type="text" name="name" required></label>
    <label>Email <input type="email" name="email" required></label>
    <label>Rol
      <select name="role">
        <option value="employee">Empleado</option>
        <option value="admin">Administrador</option>
      </select>
    </label>
    <label>Horario
      <select name="schedule_id" <?= empty($schedules)?'disabled title="No hay tabla de horarios"':'' ?>>
        <option value="">— Ninguno —</option>
        <?php foreach ($schedules as $s): ?>
          <option value="<?= (int)$s['id'] ?>"><?= h($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Contraseña
      <input type="text" name="password" placeholder="Dejar vacío para generar automáticamente">
    </label>
    <button class="btn" style="background:#e5e7eb;color:#111827;border:1px solid #374151">Crear</button>
  </form>
</div>

<section class="card">
  <h3>Listado</h3>
  <table>
    <thead><tr>
      <th>Nombre</th><th>Email</th><th>Rol</th><th>Horario</th><th>Estado</th><th>Acciones</th>
    </tr></thead>
    <tbody>
      <?php if (!$users): ?>
        <tr><td colspan="6" class="small">No hay usuarios.</td></tr>
      <?php else: foreach ($users as $u): $uid=(int)$u['id']; $active=(int)$u['active']===1; $curS=(int)($u['schedule_id'] ?? 0); ?>
        <tr>
          <form method="post" class="actions" style="gap:.4rem;flex-wrap:wrap;align-items:center">
            <?php csrf_input(); ?><input type="hidden" name="id" value="<?= $uid ?>">
            <td><input type="text" name="name" value="<?= h($u['name']) ?>" required></td>
            <td><input type="email" name="email" value="<?= h($u['email']) ?>" required></td>
            <td>
              <select name="role">
                <option value="employee" <?= $u['role']==='employee'?'selected':'' ?>>Empleado</option>
                <option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>Administrador</option>
              </select>
            </td>
            <td>
              <?php if ($schedules): ?>
                <select name="schedule_id" title="Tipo de horario">
                  <option value="">— Ninguno —</option>
                  <?php foreach ($schedules as $s): ?>
                    <option value="<?= (int)$s['id'] ?>" <?= $curS===(int)$s['id']?'selected':'' ?>>
                      <?= h($s['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              <?php else: ?>
                <span class="small muted">—</span>
              <?php endif; ?>
            </td>
            <td>
              <label class="small">
                <input type="checkbox" name="active" value="1" <?= $active ? 'checked' : '' ?>> Activo
              </label>
            </td>
            <td style="display:flex;gap:.35rem;flex-wrap:wrap">
              <!-- Botones de alto contraste -->
              <button class="btn small" name="update" value="1"
                      style="background:#16a34a;color:#071418;border:1px solid #065f46">Guardar</button>
              <button class="btn small" name="toggle" value="1"
                      style="background:#0b1220;color:#e5e7eb;border:1px solid #64748b"
                      onclick="this.form.active.checked=!this.form.active.checked">On/Off</button>
              <button class="btn small" name="resetpwd" value="1"
                      style="background:#2563eb;color:#0b1220;border:1px solid #1e40af"
                      onclick="return confirm('¿Resetear la contraseña de <?= h($u['email']) ?>?')">Reset pass</button>
            </td>
          </form>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</section>
