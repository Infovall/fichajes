<?php
csrf_check();
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/helpers.php';

$uid = user()['id'] ?? null;
if (!$uid) { redirect('index.php?page=login'); }

// Acciones de fichaje
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['in'])) {
    db()->prepare("INSERT INTO " . pfx('time_entries') . " (user_id, clock_in) VALUES (?, UTC_TIMESTAMP())")->execute([$uid]);
    flash_set('success', 'Entrada registrada');
    redirect('index.php?page=employee');
  }
  if (isset($_POST['out'])) {
    $stmt = db()->prepare("SELECT id FROM " . pfx('time_entries') . " WHERE user_id=? AND clock_out IS NULL ORDER BY clock_in DESC LIMIT 1");
    $stmt->execute([$uid]);
    $open = $stmt->fetch();
    if ($open && isset($open['id'])) {
      db()->prepare("UPDATE " . pfx('time_entries') . " SET clock_out=UTC_TIMESTAMP() WHERE id=?")->execute([$open['id']]);
      flash_set('success', 'Salida registrada');
    } else {
      flash_set('warning', 'No hay una entrada abierta para cerrar');
    }
    redirect('index.php?page=employee');
  }
}

// Turno abierto (para badge de la propia vista)
$openRow = null; $openStartUtcIso = null;
$st = db()->prepare("SELECT id, clock_in FROM " . pfx('time_entries') . " WHERE user_id=? AND clock_out IS NULL ORDER BY clock_in DESC LIMIT 1");
$st->execute([$uid]); $openRow = $st->fetch();
if ($openRow) {
  $dt = new DateTime($openRow['clock_in'], new DateTimeZone('UTC'));
  $openStartUtcIso = $dt->format('c');
}

// Últimos fichajes
$entries = db()->prepare("SELECT clock_in, clock_out FROM " . pfx('time_entries') . " WHERE user_id=? ORDER BY clock_in DESC LIMIT 15");
$entries->execute([$uid]); $entries = $entries->fetchAll();
?>
<h1>Portal del Empleado</h1>

<section class="grid">
  <div class="card">
    <h3>Fichaje rápido</h3>
    <form method="post" class="actions">
      <?php csrf_input(); ?>
      <button class="btn" name="in">Fichar ENTRADA</button>
      <button class="btn outline" name="out">Fichar SALIDA</button>
    </form>
    <p class="small">Accesos: <a class="badge" href="index.php?page=employee_month">Mi mes</a> · <a class="badge" href="index.php?page=employee_totals">Mis horas</a> · <a class="badge" href="index.php?page=request_leave">Vacaciones</a></p>
  </div>

  <div class="card">
    <h3>Mis últimos fichajes</h3>
    <table>
      <thead><tr><th>Entrada (España)</th><th>Salida (España)</th></tr></thead>
      <tbody>
        <?php foreach ($entries as $e): ?>
          <?php
            $in_es  = $e['clock_in']  ? (new DateTime($e['clock_in'],  new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Europe/Madrid'))->format('Y-m-d H:i:s') : '—';
            $out_es = $e['clock_out'] ? (new DateTime($e['clock_out'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Europe/Madrid'))->format('Y-m-d H:i:s') : '—';
          ?>
          <tr><td><?=h($in_es)?></td><td><?=h($out_es)?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
