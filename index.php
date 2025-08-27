<?php
// index.php — router con todas las rutas y display_errors activado
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/helpers.php';
require_once __DIR__ . '/core/csrf.php';
require_once __DIR__ . '/core/db.php';

$page = $_GET['page'] ?? 'home';

if (!user()) {
  $page = in_array($page, ['login','install'], true) ? $page : 'login';
}

include __DIR__ . '/views/header.php';

switch ($page) {
  // Sesión
  case 'login':   include __DIR__ . '/views/login.php'; break;
  case 'logout':  signout(); redirect('index.php?page=login'); break;

  // Empleado
  case 'employee':         require_role('employee'); include __DIR__ . '/views/employee.php'; break;
  case 'employee_month':   require_role('employee'); include __DIR__ . '/views/employee_month.php'; break;
  case 'employee_totals':  require_role('employee'); include __DIR__ . '/views/employee_totals.php'; break;
  case 'request_leave':    require_role('employee'); include __DIR__ . '/views/request_leave.php'; break;

  // Admin
  case 'admin':                require_role('admin'); include __DIR__ . '/views/admin.php'; break;
  case 'admin_users':          require_role('admin'); include __DIR__ . '/views/admin_users.php'; break;
  case 'admin_leaves':         require_role('admin'); include __DIR__ . '/views/admin_leaves.php'; break;
  case 'admin_leave_requests': require_role('admin'); include __DIR__ . '/views/admin_leave_requests.php'; break;
  case 'admin_hours':          require_role('admin'); include __DIR__ . '/views/admin_hours.php'; break;
  case 'admin_totals':         require_role('admin'); include __DIR__ . '/views/admin_totals.php'; break;
  case 'admin_calendar':       require_role('admin'); include __DIR__ . '/views/admin_calendar.php'; break;
  case 'admin_schedules':      require_role('admin'); include __DIR__ . '/views/admin_schedules.php'; break;
  case 'admin_adjustments':    require_role('admin'); include __DIR__ . '/views/admin_adjustments.php'; break;

  // Instalador
  case 'install': include __DIR__ . '/install.php'; break;

  default:
    if (user() && user()['role'] === 'admin') { redirect('index.php?page=admin'); }
    if (user()) { redirect('index.php?page=employee'); }
    redirect('index.php?page=login');
}

include __DIR__ . '/views/footer.php';
