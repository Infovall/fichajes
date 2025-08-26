<?php
require_once __DIR__ . '/core/session.php';
require_once __DIR__ . '/core/db.php';
require_once __DIR__ . '/core/helpers.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
  try{
    $pdo = db(); $p = pfx('');
    $pdo->exec("CREATE TABLE IF NOT EXISTS `{$p}departments` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `name` VARCHAR(80) UNIQUE NOT NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `{$p}users` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `email` VARCHAR(120) UNIQUE NOT NULL, `name` VARCHAR(80) NOT NULL, `password_hash` VARCHAR(255) NOT NULL, `role` ENUM('employee','admin') NOT NULL DEFAULT 'employee', `department_id` INT NULL, `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (`department_id`) REFERENCES `{$p}departments`(`id`) ON DELETE SET NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `{$p}time_entries` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `user_id` INT NOT NULL, `clock_in` DATETIME NOT NULL, `clock_out` DATETIME NULL, FOREIGN KEY (`user_id`) REFERENCES `{$p}users`(`id`) ON DELETE CASCADE, INDEX (`user_id`,`clock_in`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `{$p}leave_requests` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `user_id` INT NOT NULL, `start` DATE NOT NULL, `end` DATE NOT NULL, `reason` TEXT NULL, `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending', `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (`user_id`) REFERENCES `{$p}users`(`id`) ON DELETE CASCADE, INDEX (`user_id`,`created_at`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $pdo->exec("INSERT IGNORE INTO `{$p}departments` (`id`,`name`) VALUES (1,'General')");
    flash_set('success','Tablas listas ✅');
  } catch(Throwable $e){ flash_set('danger','Error: ' . $e->getMessage()); }
}
include __DIR__ . '/views/header.php'; ?>
<h1>Instalación</h1>
<form method="post" class="card" style="max-width:520px">
  <p>Crea/actualiza las tablas necesarias (puedes ejecutar varias veces sin problema).</p>
  <button class="btn">Crear tablas</button>
</form>
<p class="small">Después borra <code>install.php</code> del servidor.</p>
<?php include __DIR__ . '/views/footer.php'; ?>
