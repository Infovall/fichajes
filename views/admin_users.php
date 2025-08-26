<?php
// views/admin_users.php — Gestión de usuarios (CRUD básico)
declare(strict_types=1);
csrf_check();
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/helpers.php';
require_role('admin');

$info=null; $err=null; $reloadUrl='index.php?page=admin_users';

function gen_password(int $len=12): string {
  return substr(bin2hex(random_bytes(max(6,$len))),0,$len);
}
function hash_pwd(string $plain): string {
  return password_hash($plain,PASSWORD_DEFAULT);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
  try{
    if(isset($_POST['create'])){
      $name=trim($_POST['name']); $email=trim($_POST['email']);
      $role=($_POST['role']??'employee')==='admin'?'admin':'employee';
      $pwd=trim($_POST['password']); if($pwd===''){ $pwd=gen_password(); $show=$pwd; } else $show=null;
      $hash=hash_pwd($pwd);
      db()->prepare("INSERT INTO ".pfx('users')."(name,email,role,password_hash,active,created_at,updated_at) VALUES (?,?,?,?,1,NOW(),NOW())")
        ->execute([$name,$email,$role,$hash]);
      $info="Usuario creado: $email".($show?" — Contraseña: $show":"");
    }
    if(isset($_POST['update'])){
      $id=(int)$_POST['id']; $name=trim($_POST['name']); $email=trim($_POST['email']);
      $role=($_POST['role']==='admin')?'admin':'employee';
      db()->prepare("UPDATE ".pfx('users')." SET name=?,email=?,role=?,updated_at=NOW() WHERE id=?")->execute([$name,$email,$role,$id]);
      $info="Usuario actualizado: $email";
    }
    if(isset($_POST['toggle'])){
      $id=(int)$_POST['id']; $active=isset($_POST['active'])?1:0;
      db()->prepare("UPDATE ".pfx('users')." SET active=?,updated_at=NOW() WHERE id=?")->execute([$active,$id]);
      $info="Estado cambiado ID $id";
    }
    if(isset($_POST['resetpwd'])){
      $id=(int)$_POST['id']; $new=gen_password(); $hash=hash_pwd($new);
      db()->prepare("UPDATE ".pfx('users')." SET password_hash=?,updated_at=NOW() WHERE id=?")->execute([$hash,$id]);
      $info="Contraseña nueva ID $id: $new";
    }
  }catch(Throwable $e){ $err=$e->getMessage(); }
}

$users=db()->query("SELECT id,name,email,role,active FROM ".pfx('users')." ORDER BY role='admin' DESC,name ASC")->fetchAll();
?>
<h1>Usuarios</h1>

<?php if($info): ?>
  <div class="card" style="border-left:4px solid #22c55e"><?=$info?> <span class="small">Recargando…</span></div>
  <script>setTimeout(()=>location.href='<?=$reloadUrl?>',800)</script>
<?php endif; ?>
<?php if($err): ?><div class="card" style="border-left:4px solid #ef4444"><strong>Error:</strong> <?=$err?></div><?php endif; ?>

<div class="card">
  <h3>Crear usuario</h3>
  <form method="post" class="actions">
    <?php csrf_input(); ?>
    <input type="hidden" name="create" value="1">
    <label>Nombre <input type="text" name="name" required></label>
    <label>Email <input type="email" name="email" required></label>
    <label>Rol <select name="role"><option value="employee">Empleado</option><option value="admin">Admin</option></select></label>
    <label>Contraseña <input type="text" name="password" placeholder="vacío=auto"></label>
    <button class="btn">Crear</button>
  </form>
</div>

<section class="card">
  <h3>Listado</h3>
  <table>
    <thead><tr><th>Nombre</th><th>Email</th><th>Rol</th><th>Activo</th><th>Acciones</th></tr></thead>
    <tbody>
      <?php foreach($users as $u): ?>
        <tr>
          <form method="post" class="actions">
            <?php csrf_input(); ?>
            <input type="hidden" name="id" value="<?=$u['id']?>">
            <td><input type="text" name="name" value="<?=h($u['name'])?>"></td>
            <td><input type="email" name="email" value="<?=h($u['email'])?>"></td>
            <td><select name="role"><option value="employee" <?=$u['role']==='employee'?'selected':''?>>Empleado</option><option value="admin" <?=$u['role']==='admin'?'selected':''?>>Admin</option></select></td>
            <td><input type="checkbox" name="active" value="1" <?=$u['active']?'checked':''?>></td>
            <td>
              <button class="btn small" name="update" value="1">Guardar</button>
              <button class="btn small" name="toggle" value="1">On/Off</button>
              <button class="btn small" name="resetpwd" value="1">Reset pass</button>
            </td>
          </form>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>
