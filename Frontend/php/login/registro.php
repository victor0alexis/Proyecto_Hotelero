<?php
include("../conexion.php");
session_start();

$mensaje = "";
$mensaje_exito = "";
$rol = $_GET['rol'] ?? null;

// Inicializar valores del formulario para persistencia
$nombre = $_POST["nombre"] ?? "";
$email = $_POST["email"] ?? "";
$telefono = $_POST["telefono"] ?? "";
$clave = $_POST["clave"] ?? "";

if (!empty($_POST["btnregistrar"])) {
    if (empty($nombre) || empty($email) || empty($clave)) {
        $mensaje = "Error: Todos los campos son obligatorios.";
    } else {
        $rol = $_POST["rol"];

        // Validaciones
        if (!preg_match('/^[[:alpha:] ]+$/u', $nombre)) {
            $mensaje = "Error: El nombre solo debe contener letras y espacios.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mensaje = "Error: La dirección de correo no es válida.";
        } elseif ($rol === 'huesped' && !preg_match('/^[0-9]{7,10}$/', $telefono)) {
            $mensaje = "Error: El teléfono debe tener entre 7 y 10 dígitos numéricos.";
        } else {
            // Verificar si el username ya existe
            $check_user = pg_query_params($conn, "SELECT 1 FROM usuario WHERE username = $1", [$nombre]);
            if (pg_num_rows($check_user) > 0) {
                $mensaje = "Error: El nombre de usuario ya está registrado.";
            } else {
                // Insertar en tabla usuario
                $insert_usuario = pg_query_params($conn,
                    "INSERT INTO usuario (username, clave, rol) VALUES ($1, md5($2), $3) RETURNING id_usuario",
                    [$nombre, $clave, $rol]
                );

                if ($insert_usuario && $row = pg_fetch_assoc($insert_usuario)) {
                    $id_usuario = $row["id_usuario"];
                    $codigo = rand(100000, 999999);

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
                    <input type="text" name="nombre" value="<?= htmlspecialchars($nombre) ?>" pattern=".*\S.*" required>
                    <label>Nombre de Usuario</label>
                </div>

                <div class="input-group">
                    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" pattern=".*\S.*" required>
                    <label>Email</label>
                </div>

                <?php if ($rol === "huesped"): ?>
                    <div class="input-group">
                        <input type="text" name="telefono" value="<?= htmlspecialchars($telefono) ?>" pattern=".*\S.*" required>
                        <label>Teléfono</label>
                    </div>
                <?php endif; ?>

                <div class="input-group">
                    <input type="password" name="clave" pattern=".*\S.*" required>
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
