<?php
if (!isset($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
function csrf_input(){ echo '<input type="hidden" name="csrf" value="'.h($_SESSION['csrf']).'">'; }
function csrf_check(){ if ($_SERVER['REQUEST_METHOD']==='POST'){ $ok = isset($_POST['csrf']) && hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf']); if (!$ok){ http_response_code(400); die('CSRF token inválido'); } } }
