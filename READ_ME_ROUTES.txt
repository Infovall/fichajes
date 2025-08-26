Rutas nuevas para index.php:

  case 'admin_adjustments': require_role('admin'); include __DIR__ . '/views/admin_adjustments.php'; break;
  case 'admin_totals': require_role('admin'); include __DIR__ . '/views/admin_totals.php'; break;
  case 'employee_totals': require_role('employee'); include __DIR__ . '/views/employee_totals.php'; break;

Ya están añadidos accesos directos en admin.php y employee.php.
