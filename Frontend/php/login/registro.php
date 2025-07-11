<?php
include("../conexion.php");
session_start();

$mensaje = "";
$mensaje_exito = "";
$rol = $_GET['rol'] ?? null;

// Inicializar valores del formulario para persistencia
$username = trim($_POST["username"] ?? "");
$clave = $_POST["clave"] ?? "";
$nombre = trim($_POST["nombre"] ?? "");
$email = trim($_POST["email"] ?? "");
$telefono = trim($_POST["telefono"] ?? "");

if (!empty($_POST["btnregistrar"])) {
    if (empty($username) || empty($clave) || empty($nombre) || empty($email)) {
        $mensaje = "Error: Todos los campos obligatorios deben completarse.";
    } else {
        $rol = $_POST["rol"];

        // Validaciones
        if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
            $mensaje = "Error: El nombre de usuario debe contener entre 3 y 50 caracteres alfanuméricos o guion bajo.";
        } elseif (strlen($clave) < 6) {
            $mensaje = "Error: La contraseña debe tener al menos 6 caracteres.";
        } elseif (!preg_match('/^[[:alpha:] ]+$/u', $nombre)) {
            $mensaje = "Error: El nombre solo debe contener letras y espacios.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mensaje = "Error: El email no es válido.";
        } elseif ($rol === 'huesped' && !preg_match('/^[0-9]{7,10}$/', $telefono)) {
            $mensaje = "Error: El teléfono debe tener entre 7 y 10 dígitos numéricos.";
        } else {
            // Verificar si el username ya existe
            $check_user = pg_query_params($conn, "SELECT 1 FROM usuario WHERE username = $1", [$username]);
            if (pg_num_rows($check_user) > 0) {
                $mensaje = "Error: El nombre de usuario ya está registrado.";
            } else {
                // Insertar en Usuario
                $insert_usuario = pg_query_params($conn,
                    "INSERT INTO usuario (username, clave, rol) VALUES ($1, md5($2), $3) RETURNING id_usuario",
                    [$username, $clave, $rol]
                );

                if ($insert_usuario && $row = pg_fetch_assoc($insert_usuario)) {
                    $id_usuario = $row["id_usuario"];
                    $codigo = strval(rand(100000, 999999));

                    if ($rol === 'huesped') {
                        pg_query_params($conn,
                            "INSERT INTO huesped (id_usuario, nombre, email, telefono, verificado, codigo_verificacion)
                             VALUES ($1, $2, $3, $4, false, $5)",
                            [$id_usuario, $nombre, $email, $telefono, $codigo]
                        );
                    } elseif ($rol === 'admin') {
                        pg_query_params($conn,
                            "INSERT INTO administrador (id_usuario, nombre, email, verificado, codigo_verificacion)
                             VALUES ($1, $2, $3, false, $4)",
                            [$id_usuario, $nombre, $email, $codigo]
                        );
                    }

                    // Enviar código de verificación
                    include("mail/enviar_codigo.php");
                    enviarCodigoVerificacion($email, $codigo);

                    $mensaje_exito = "¡Registro exitoso! Redirigiendo a verificación de correo...";
                    echo "<script>
                            setTimeout(function() {
                                window.location.href = 'mail/verificar.php?rol=$rol&email=$email';
                            }, 5000);
                          </script>";
                } else {
                    $mensaje = "Error: No se pudo registrar el usuario.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro</title>
    <link rel="stylesheet" href="../../css/style_login.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<section class="login-section">
    <div class="formulario animate">
        <?php if (!$rol): ?>
            <h1 style="color: black; text-align: center; margin-bottom: 30px;">¿Qué tipo de cuenta deseas crear?</h1>
            <div class="role-buttons-container">
                <a href="registro.php?rol=huesped" class="role-button">Registrarse como Huésped</a>
                <a href="registro.php?rol=admin" class="role-button">Registrarse como Admin</a>
            </div>
                <div class="registrarse">
                    <a href="login.php">Volver</a>
                </div>
        <?php else: ?>
            <h1>Registro</h1>

            <?php if (!empty($mensaje)): ?>
                <div class="alert"><?= htmlspecialchars($mensaje) ?></div>
            <?php elseif (!empty($mensaje_exito)): ?>
                <div class="alert" style="background-color: #d1e7dd; color: #0f5132;"><?= htmlspecialchars($mensaje_exito) ?></div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="rol" value="<?= htmlspecialchars($rol) ?>">

                <div class="input-group">
                    <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" required pattern="^[a-zA-Z0-9_]{3,50}$">
                    <label>Nombre de Usuario</label>
                </div>

                <div class="input-group">
                    <input type="text" name="nombre" value="<?= htmlspecialchars($nombre) ?>">
                    <label>Nombre Completo</label>
                </div>

                <div class="input-group">
                    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                    <label>Email</label>
                </div>

                <?php if ($rol === "huesped"): ?>
                    <div class="input-group">
                        <input type="text" name="telefono" value="<?= htmlspecialchars($telefono) ?>" pattern="^[0-9]{7,10}$" required>
                        <label>Teléfono</label>
                    </div>
                <?php endif; ?>

                <div class="input-group">
                    <input type="password" name="clave" required pattern=".{6,}">
                    <label>Contraseña</label>
                </div>

                <input name="btnregistrar" type="submit" value="Registrarse">
                <div class="registrarse">
                    <a href="registro.php">Volver</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</section>
</body>
</html>
