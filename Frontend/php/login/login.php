
<?php
include("../conexion.php");
session_start();

$mensaje = "";
$redirect_url = $_GET['redirect'] ?? '';

if (!empty($_POST["btningresar"])) {
    $username = trim($_POST["user"] ?? '');
    $pass = $_POST["pass"] ?? '';

    if (empty($username) || empty($pass)) {
        $mensaje = "Todos los campos son obligatorios.";
    } else {
        // Buscar al usuario por username
        $query_usuario_base = pg_query_params($conn, "SELECT * FROM usuario WHERE username = $1", array($username));

        if (pg_num_rows($query_usuario_base) > 0) {
            $usuario = pg_fetch_assoc($query_usuario_base);
            $id_usuario = $usuario["id_usuario"];

            // Verificar contraseña con md5
            $query_credenciales = pg_query_params($conn, "SELECT * FROM usuario WHERE id_usuario = $1 AND clave = md5($2)", array($id_usuario, $pass));

            if (pg_num_rows($query_credenciales) > 0) {
                // Determinar si es admin o huesped
                $query_admin = pg_query_params($conn, "SELECT email, verificado, codigo_verificacion, nombre FROM administrador WHERE id_usuario = $1", array($id_usuario));
                $query_huesped = pg_query_params($conn, "SELECT id_huesped, email, verificado, codigo_verificacion, nombre FROM huesped WHERE id_usuario = $1", array($id_usuario));

                if (pg_num_rows($query_admin) > 0) {
                    $datos = pg_fetch_assoc($query_admin);
                    $rol = "admin";
                    $_SESSION["nombre"] = $datos["nombre"];
                    $_SESSION["email"] = $datos["email"];
                } elseif (pg_num_rows($query_huesped) > 0) {
                    $datos = pg_fetch_assoc($query_huesped);
                    $rol = "huesped";
                    $_SESSION["id_huesped"] = $datos["id_huesped"];
                    $_SESSION["username"] = $datos["nombre"];
                } else {
                    $mensaje = "Usuario no asociado a ningún rol válido.";
                    $rol = null;
                }

                if (!empty($rol)) {
                    // Verificación de cuenta
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

                    // Asignar datos comunes
                    $_SESSION["id_usuario"] = $id_usuario;
                    $_SESSION["rol"] = $rol;
                    $_SESSION["username"] = $usuario["username"];

                    // Redirección
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
                }
            } else {
                $mensaje = "Contraseña incorrecta.";
            }
        } else {
            $mensaje = "El nombre de usuario no está registrado.";
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
    <label>Nombre de usuario</label>
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
