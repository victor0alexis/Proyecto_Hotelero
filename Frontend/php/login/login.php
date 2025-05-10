<?php
include("../conexion.php");         //conexion BD.
session_start();                   // Iniciar seccion para manejar variables "$_SESSION".

$mensaje = "";

if (!empty($_POST["btningresar"])) {                              // Si se envió el formulario.
    $nombre = trim($_POST["user"] ?? '');                //  Obtiene los valores enviados por el formulario.
    $pass = $_POST["pass"] ?? '';

    //Si alguno de los campos está vacío, se genera un mensaje de error.
    if (empty($nombre) || empty($pass)) {
        $mensaje = "Todos los campos son obligatorios.";
    } else {
        // Verificamos en tabla administrador, los datos
        $query_admin = pg_query_params($conn, "SELECT id_usuario FROM administrador WHERE nombre = $1", array($nombre));

          if (pg_num_rows($query_admin) > 0) {                   // Si se encuentra un administrador en la tabla.
            $admin = pg_fetch_assoc($query_admin);
            $id_usuario = $admin["id_usuario"];               // Obtenemos el id de usuario.
            $rol = "admin";                                  // Obtenemos el rol de usuario.
        } else {
            // Verificamos en tabla huesped
          $query_huesped = pg_query_params($conn, "SELECT id_usuario FROM huesped WHERE nombre = $1", array($nombre));

          if (pg_num_rows($query_huesped) > 0) {                 // Si se encuentra un huesped en la tabla.
              $huesped = pg_fetch_assoc($query_huesped);       
              $id_usuario = $huesped["id_usuario"];            // Obtenemos el id de usuario.
              $rol = "huesped";                               // Obtenemos el rol de usuario.
          } else {

            // Si no está en ninguna tabla, se muestra mensaje de error y se cancela la búsqueda.
              $mensaje = "El nombre no está registrado como administrador ni como huésped.";
              $id_usuario = null;
          }
        }

        //Verificamos en Tabla "USUARIO", si coincide el ID_USUARIO con Clave
        if ($id_usuario) {
            $query_usuario = pg_query_params($conn, "SELECT * FROM usuario WHERE id_usuario = $1 AND clave = md5($2)", array($id_usuario, $pass));

            //Si la consulta devuelve resultados, entonces la contraseña es correcta.
            if (pg_num_rows($query_usuario) > 0) {

                // Verificar si el usuario está verificado
                if ($rol === "admin") {
                    $check_verif = pg_query_params($conn, "SELECT email, verificado, codigo_verificacion FROM administrador WHERE id_usuario = $1", array($id_usuario));
                } else {
                    $check_verif = pg_query_params($conn, "SELECT email, verificado, codigo_verificacion FROM huesped WHERE id_usuario = $1", array($id_usuario));
                }

                //Validación de cuenta verificada:
                if ($check_verif && $datos = pg_fetch_assoc($check_verif)) {
                  // Debug temporal
                  error_log("Valor verificado: " . var_export($datos['verificado'], true));

                  if ($datos['verificado'] !== 't' && $datos['verificado'] !== true) {
                        include("mail/enviar_codigo.php");
                        enviarCodigoVerificacion($datos['email'], $datos['codigo_verificacion']);
                        echo "<script>
                                alert('Tu cuenta aún no está verificada. Se ha reenviado el código a tu correo.');
                                window.location.href = 'mail/verificar.php?rol=$rol&email=" . urlencode($datos['email']) . "';
                              </script>";
                        exit();
                    }
                }

                // Usuario verificado: guardar sesión y redirigir
                $usuario = pg_fetch_assoc($query_usuario);
                $_SESSION["id_usuario"] = $usuario["id_usuario"];
                $_SESSION["username"] = $usuario["username"];
                $_SESSION["rol"] = $usuario["rol"];

                if ($rol === "admin") {
                    header("Location: ../admin/panel_admin.php");
                } elseif ($rol === "huesped") {
                    header("Location: ../huesped/panel_huesped.php");
                } else {
                    $mensaje = "Rol no reconocido.";
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
  <title>Iniciar Sesión - HOTEL H</title>
  
  <link rel="stylesheet" href="../../css/style_login.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
  
</head>
<body>

  <!-------Encabezado---------->

<header class="header">

  <div class="logo">HOTEL <span>H</span></div>
  <nav>
    <ul class="nav-links">
      <li><a href="../../pages/index.html">INICIO</a></li>
      <li><a href="../../pages/habitaciones.html">HABITACIONES</a></li>
      <li><a href="../../pages/servicios.html">SERVICIOS</a></li>
      <li><a href="../../pages/blog.html">BLOG</a></li>
      <li><a href="../../pages/contacto.html">CONTACTO</a></li>
    </ul>
  </nav>
  <div class="right-nav">
  
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
        <label>Nombre</label>
      </div>
      <!-------password---------->
      <div class="input-group">
        <input type="password" name="pass" required>
        <label>Contraseña</label>
      </div>
      <!-------boton Iniciar secion---------->
      <input name="btningresar" type="submit" value="Iniciar Sesión">
      <!-------Link Registrarse---------->
      <div class="registrarse">
        ¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a>
      </div>
    </form>

  </div>
  
</section>

</body>
</html>
