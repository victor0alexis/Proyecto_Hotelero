<?php
include("../../../conexion.php");
session_start();

// Verificar que el usuario es un administrador
if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

$mensaje = "";

// Inicializar variables para preservar datos del formulario
$username = $clave = $nombre = $email = $telefono = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $clave = $_POST["clave"];
    $nombre = trim($_POST["nombre"]);
    $email = trim($_POST["email"]);
    $telefono = trim($_POST["telefono"]);

    // Validaciones
    if (empty($username) || empty($clave) || empty($nombre) || empty($email) || empty($telefono)) {
        $mensaje = "Todos los campos son obligatorios.";
    } elseif (!preg_match('/^[\p{L} ]+$/u', $nombre)) {
        $mensaje = "El nombre solo debe contener letras y espacios.";
    } elseif (strlen($clave) < 6) {
        $mensaje = "La contraseña debe tener al menos 6 caracteres.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "El correo electrónico no tiene un formato válido.";
    } elseif (!empty($telefono) && !preg_match('/^\d{7,10}$/', $telefono)) {
        $mensaje = "El número de teléfono debe contener solo dígitos y tener entre 7 y 10 caracteres.";
    } else {
        // Verificar si el usuario ya existe
        $verificar = pg_query_params($conn, "SELECT * FROM usuario WHERE username = $1", array($username));

        if (pg_num_rows($verificar) > 0) {
            $mensaje = "El nombre de usuario ya está en uso.";
        } else {
            $rol = 'huesped';
            $clave_hash = md5($clave);

            // Insertar en tabla Usuario
            $insert_usuario = pg_query_params(
                $conn,
                "INSERT INTO usuario (username, clave, rol) VALUES ($1, $2, $3) RETURNING id_usuario",
                array($username, $clave_hash, $rol)
            );

            if ($insert_usuario && $row = pg_fetch_assoc($insert_usuario)) {
                $id_usuario = $row['id_usuario'];
                $codigo_verificacion = strval(rand(100000, 999999));

                // Insertar en tabla Huesped
                $insert_huesped = pg_query_params(
                    $conn,
                    "INSERT INTO huesped (id_usuario, nombre, email, telefono, verificado, codigo_verificacion)
                     VALUES ($1, $2, $3, $4, false, $5)",
                    array($id_usuario, $nombre, $email, $telefono, $codigo_verificacion)
                );

                if ($insert_huesped) {
                    header("Location: index.php?mensaje=Huésped+registrado+correctamente");
                    exit();
                } else {
                    $mensaje = "Error al registrar en la tabla huésped.";
                }
            } else {
                $mensaje = "Error al registrar usuario.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Huésped</title>
    <link rel="stylesheet" href="../../../../css/CRUD/style_crud_insert.css">
</head>
<body>
<div class="form-container">
    <h2>Registrar Huésped</h2>

    <?php if ($mensaje): ?>
        <div class="mensaje"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="username">Usuario:</label>
            <input type="text" name="username" required value="<?= htmlspecialchars($username) ?>">
        </div>

        <div class="form-group">
            <label for="clave">Contraseña:</label>
            <input type="password" name="clave" required>
        </div>

        <div class="form-group">
            <label for="nombre">Nombre Completo:</label>
            <input type="text" name="nombre" required value="<?= htmlspecialchars($nombre) ?>">
        </div>

        <div class="form-group">
            <label for="email">Correo Electrónico:</label>
            <input type="email" name="email" required value="<?= htmlspecialchars($email) ?>">
        </div>

        <div class="form-group">
            <label for="telefono">Teléfono:</label>
            <input type="text" name="telefono" value="<?= htmlspecialchars($telefono) ?>">
        </div>

        <div class="form-buttons">
            <button type="submit">Registrar</button>
            <a href="index.php" class="btn-volver">Volver</a>
        </div>
    </form>
</div>
</body>
</html>
