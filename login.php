<?php
session_start();

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Login - Sistema Administrativo</title>
  <style>
    body { 
        font-family: Arial, sans-serif; 
        padding: 20px;
        background-color: #f5f5f5;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
    }
    .box { 
        width: 350px; 
        margin: 0 auto; 
        border: 1px solid #ddd; 
        padding: 30px; 
        border-radius: 8px;
        background: white;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    input { 
        width: 100%; 
        padding: 10px; 
        margin: 8px 0; 
        box-sizing: border-box;
        border: 1px solid #ccc;
        border-radius: 4px;
    }
    button { 
        padding: 10px 12px; 
        width: 100%; 
        background: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    button:hover {
        background: #0056b3;
    }
    .error {
        color: red;
        background: #ffeaea;
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 15px;
        border: 1px solid red;
    }
  </style>
</head>
<body>
  <div class="box">
    <h2 style="text-align: center; margin-bottom: 25px;">Acceso Administrativo</h2>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="error"><?= $_SESSION['error']; ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <form action="process_login.php" method="post">
      <label for="nombre">Nombre de Usuario</label>
      <input type="text" id="nombre" name="nombre" required placeholder="Administrador">

      <label for="password">Contraseña</label>
      <input type="password" id="password" name="password" required>

      <button type="submit">Entrar al Sistema</button>
    </form>
    
    <p style="text-align: center; margin-top: 20px; color: #666; font-size: 12px;">
        Solo personal autorizado - Acceso Administrativo
    </p>
  </div>
</body>
</html>