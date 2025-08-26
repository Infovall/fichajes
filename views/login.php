<?php csrf_check(); ?>
<h1>Entrar</h1>
<?php
if($_SERVER['REQUEST_METHOD']==='POST'){
  [$ok,$msg] = signin($_POST['email'] ?? '', $_POST['password'] ?? '');
  if($ok){ redirect('index.php'); } else { flash_set('danger',$msg); }
}
?>
<form method="post" class="card" style="max-width:460px">
  <?php csrf_input(); ?>
  <label>Email <input name="email" type="email" required autofocus></label>
  <label>Contraseña <input name="password" type="password" required></label>
  <div class="actions"><button class="btn">Entrar</button></div>
  <p class="small">Usa el usuario insertado en la BD.</p>
</form>
