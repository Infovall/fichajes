<?php
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function redirect($url){ header("Location: " . $url); exit; }
function flash_set($type,$msg){ $_SESSION['flash'][] = [$type, $msg]; }
function flash_out(){ if (!empty($_SESSION['flash'])){ foreach($_SESSION['flash'] as [$t,$m]){ echo '<div class="flash '.$t.'">'.h($m).'</div>'; } $_SESSION['flash'] = []; } }
