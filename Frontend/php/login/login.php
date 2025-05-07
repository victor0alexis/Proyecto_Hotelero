<?php
include("../conexion.php");     // Incluir conexion
session_start();               // Iniciar seccion para manejar variables "$_SESSION"

$mensaje = "";

//Validar Nombre de Usuario de las tablas admin y huesped, con la obtenida en el formulario

if (!empty($_POST["btningresar"])) {                  //cuando se preciona el boton
    $nombre = trim($_POST["user"] ?? '');    // guardamos datos en variable $nombre
    $pass = $_POST["pass"] ?? '';                   // guardamos datos en variable $pass

    if (empty($nombre) || empty($pass)) {
        $mensaje = "Todos los campos son obligatorios.";       //en el caso de que esten vacios
    } else {
        // Buscar primero en administrador (si "nombre" esta asociado a un "id_usuario")
        $query_admin = pg_query_params($conn, "SELECT id_usuario FROM administrador WHERE nombre = $1", array($nombre));
        
        //si se encuentra un admistrador se toma su "$id_usuario"
        if (pg_num_rows($query_admin) > 0) {
            $admin = pg_fetch_assoc($query_admin);
            $id_usuario = $admin["id_usuario"];
        
        // si no, se busca en la tabla huesped
        } else {
            // Si "nombre" esta asociado a un "id_usuario"
            $query_huesped = pg_query_params($conn, "SELECT id_usuario FROM huesped WHERE nombre = $1", array($nombre));
            
            //si se encuentra un huesped se toma su "$id_usuario"
            if (pg_num_rows($query_huesped) > 0) {
                $huesped = pg_fetch_assoc($query_huesped);
                $id_usuario = $huesped["id_usuario"];
            //En el caso de que no encuentre niun nombre asociado a "id_usuario"
            } else {
                $mensaje = "El nombre no está registrado como administrador ni como huésped.";
                $id_usuario = null;
            }
        }

        // Validar CLAVE en tabla usuario, con "id_usuario" ya obtenido.
        if ($id_usuario) {
            $query_usuario = pg_query_params
            ($conn, "SELECT * FROM usuario WHERE id_usuario = $1 AND clave = md5($2)", array($id_usuario, $pass));

            //Se guarda seccion, en variables "id_usuario", "username", "rol".
            if (pg_num_rows($query_usuario) > 0) {
                $usuario = pg_fetch_assoc($query_usuario);  //varible que contiene todos los datos que conciden 
                $_SESSION["id_usuario"] = $usuario["id_usuario"];
                $_SESSION["username"] = $usuario["username"];
                $_SESSION["rol"] = $usuario["rol"];

                // Redireccionar según el rol
                if ($usuario["rol"] === "admin") {
                    header("Location: ../admin/panel_admin.php");
                } elseif($usuario["rol"] === "huesped") {
                    header("Location: ../huesped/panel_huesped.php");
                } else{
                  $menasje = "Rol no reconocido";
                }
                exit();
            } else {
                $mensaje = "Contraseña incorrecta.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar Sesión - Y Hotel</title>
  <link rel="stylesheet" href="../../css/style_login.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>

  <!-------Encabezado---------->

<header class="header">

  <div class="logo">Y <span>Hotel</span></div>
  <nav>
    <ul class="nav-links">
      <li><a href="../../pages/index.html">Inicio</a></li>
      <li><a href="../../pages/habitaciones.html">Habitaciones</a></li>
      <li><a href="../../pages/servicios.html">Servicios</a></li>
      <li><a href="../../pages/blog.html">Blog</a></li>
      <li><a href="../../pages/contacto.html">Contactos</a></li>
    </ul>
  </nav>
  <div class="right-nav">
    <a href="login.php" class="btn-login">Login ➜</a>
  </div>

</header>

  <!-------Formulario Login---------->

<section class="login-section">

  <div class="formulario animate">
    <h1>Inicio de Sesión</h1>
      <!-------incluimos mensaje, segun sea necesario---------->
    <?php if (!empty($mensaje)) : ?>
      <div class="alert"><?= $mensaje ?></div>
    <?php endif; ?>

    <form method="post">
      <!-------usuario---------->
      <div class="input-group">
        <input type="text" name="user" required>
        <label>Nombre de Usuario</label>
      </div>
      <!-------password---------->
      <div class="input-group">
        <input type="password" name="pass" required>
        <label>Clave Usuario</label>
      </div>
      <!-------boton Iniciar secion---------->
      <input name="btningresar" type="submit" value="Iniciar sesión">
      <!-------Link Registrarse---------->
      <div class="registrarse">
        ¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a>
      </div>
    </form>

  </div>
  
</section>

</body>
</html>
