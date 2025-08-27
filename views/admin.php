<?php
// views/admin.php — Panel principal del administrador
declare(strict_types=1);
require_role('admin');
?>
<h1>Panel de administración</h1>

<div class="grid">
  <div class="card">
    <h3>⏱️ Horas</h3>
    <p class="small">Listado de fichajes por día.</p>
    <a class="btn" href="index.php?page=admin_hours">Ir a Horas</a>
  </div>

  <div class="card">
    <h3>📅 Calendario</h3>
    <p class="small">Vacaciones aprobadas y colores por sección.</p>
    <a class="btn" href="index.php?page=admin_calendar">Ir al Calendario</a>
  </div>

  <div class="card">
    <h3>🗓️ Horarios</h3>
    <p class="small">Tipos de horario y asignación por usuario.</p>
    <a class="btn" href="index.php?page=admin_schedules">Gestionar Horarios</a>
  </div>

  <div class="card">
    <h3>🏖️ Solicitudes</h3>
    <p class="small">Aprobar o rechazar solicitudes de vacaciones.</p>
    <a class="btn" href="index.php?page=admin_leave_requests">Gestionar Solicitudes</a>
  </div>

  <div class="card">
    <h3>🧑‍🤝‍🧑 Usuarios</h3>
    <p class="small">Crear, editar, activar/desactivar, asignar horarios y resetear contraseña.</p>
    <a class="btn" href="index.php?page=admin_users">Gestionar Usuarios</a>
  </div>
</div>
