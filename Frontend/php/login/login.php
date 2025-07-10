<?php

$mensaje = "";
$redirect_url = $_GET['redirect'] ?? '';

if (!empty($_POST["btningresar"])) {
    $nombre = trim($_POST["user"] ?? '');
    $pass = $_POST["pass"] ?? '';

    if (empty($nombre) || empty($pass)) {
        $mensaje = "Todos los campos son obligatorios.";
    } else {
        // Buscar en administrador
        $query_admin = pg_query_params($conn, "SELECT id_usuario FROM administrador WHERE nombre = $1", array($nombre));
        if (pg_num_rows($query_admin) > 0) {
            $admin = pg_fetch_assoc($query_admin);
            $id_usuario = $admin["id_usuario"];
            $rol = "admin";
        } else {
            // Buscar en huesped
            $query_huesped = pg_query_params($conn, "SELECT id_usuario FROM huesped WHERE nombre = $1", array($nombre));
            if (pg_num_rows($query_huesped) > 0) {
                $huesped = pg_fetch_assoc($query_huesped);
                $id_usuario = $huesped["id_usuario"];
                $rol = "huesped";
            } else {
                $mensaje = "El nombre no está registrado.";
                $id_usuario = null;
            }
        }

        if ($id_usuario) {
            $query_usuario = pg_query_params($conn, "SELECT * FROM usuario WHERE id_usuario = $1 AND clave = md5($2)", array($id_usuario, $pass));
            if (pg_num_rows($query_usuario) > 0) {

                // Consultar datos de verificación desde la tabla según el rol
                $check_verif = pg_query_params($conn, "SELECT email, verificado, codigo_verificacion FROM " . $rol . " WHERE id_usuario = $1", array($id_usuario));

                if ($check_verif && $datos = pg_fetch_assoc($check_verif)) {
                    // Convertir el valor devuelto por PostgreSQL en booleano confiable
                    $is_verified = ($datos['verificado'] === true || $datos['verificado'] === 't');

                    if (!$is_verified) {
                        include("mail/enviar_codigo.php");
                        enviarCodigoVerificacion($datos['email'], $datos['codigo_verificacion']);
                        echo "<script>
                                alert('Tu cuenta aún no está verificada. Se ha reenviado el código a tu correo.');
                                window.location.href = 'mail/verificar.php?rol=$rol&email=" . urlencode($datos['email']) . "';
                              </script>";
                        exit();
                    }
                }

                $usuario = pg_fetch_assoc($query_usuario);

                // Datos generales
                $_SESSION["id_usuario"] = $usuario["id_usuario"];
                $_SESSION["rol"] = $rol;
                $_SESSION["username"] = $usuario["username"];

                // Cargar datos específicos según el rol
                if ($rol === "huesped") {
                    $datos_huesped = pg_fetch_assoc(pg_query_params($conn, "SELECT id_huesped, nombre FROM huesped WHERE id_usuario = $1", [$usuario["id_usuario"]]));
                    $_SESSION["id_huesped"] = $datos_huesped["id_huesped"];
                    $_SESSION["username"] = $datos_huesped["nombre"];
                } elseif ($rol === "admin") {
                    $datos_admin = pg_fetch_assoc(pg_query_params($conn, "SELECT nombre, email FROM administrador WHERE id_usuario = $1", [$usuario["id_usuario"]]));
                    $_SESSION["nombre"] = $datos_admin["nombre"];
                    $_SESSION["email"] = $datos_admin["email"];
                }

                // Redirección según rol
                if ($rol === "admin") {
                    header("Location: ../admin/panel_admin.php");
                    exit();
                } elseif ($rol === "huesped") {
                    if ($redirect_url && strpos($redirect_url, 'http://') === false && strpos($redirect_url, 'https://') === false) {
                        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
                        $host = $_SERVER['HTTP_HOST'];
                        $redirect_path = ltrim($redirect_url, '/');
                        header("Location: $protocol://$host/$redirect_path");
                        exit();
                    } else {
                        header("Location: ../../pages/index.php");
                        exit();
                    }
                }
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
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Iniciar Sesión</title>
  <link rel="stylesheet" href="../../css/style_login.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>

<header class="header">
  <div class="logo">HOTEL</div>
  <nav>
    <ul class="nav-links">
      <li><a href="../../pages/index.php">INICIO</a></li>
      <li><a href="../../pages/habitacion/habitaciones.php">HABITACIONES</a></li>
      <li><a href="../../pages/servicios/servicios.php">SERVICIOS</a></li>
      <li><a href="../../pages/contacto.html">CONTACTO</a></li>
    </ul>
  </nav>
  <div class="right-nav"></div>
</header>

<section class="login-section">
  <div class="formulario animate">
    <h1>Inicio de Sesión</h1>
    <?php if (!empty($mensaje)) : ?>
      <div class="alert"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="input-group">
        <input type="text" name="user" required>
        <label>Nombre</label>
      </div>
      <div class="input-group">
        <input type="password" name="pass" required>
        <label>Contraseña</label>
      </div>
      <input name="btningresar" type="submit" value="Iniciar Sesión">
      <div class="registrarse">
        ¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a>
      </div>
    </form>
  </div>
</section>

<!-- ======= PIE DE PÁGINA ======= -->
<footer class="footer">
    <p>&copy; 2025  Hotel. Todos los derechos reservados.</p>
</footer>

</body>

</html>
