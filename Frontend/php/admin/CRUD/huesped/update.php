<?php
include("../../../conexion.php");
session_start();

// Verificar sesión de administrador
if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

$mensaje = "";

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id_usuario = intval($_GET['id']);

$consulta = pg_query_params($conn, "
    SELECT h.id_huesped, u.id_usuario, u.username, h.nombre, h.email, h.telefono
    FROM huesped h
    JOIN usuario u ON h.id_usuario = u.id_usuario
    WHERE u.id_usuario = $1
", array($id_usuario));

$huesped = pg_fetch_assoc($consulta);

if (!$huesped) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);

    $verificar_username = pg_query_params($conn, "
        SELECT id_usuario FROM usuario WHERE username = $1 AND id_usuario != $2
    ", array($username, $id_usuario));

    if (pg_num_rows($verificar_username) > 0) {
        $mensaje = "El nombre de usuario ya está en uso.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,}$/', $username)) {
        $mensaje = "El nombre de usuario debe tener al menos 3 caracteres y no contener espacios.";
    } elseif (!preg_match('/^[a-zA-ZÁÉÍÓÚáéíóúñÑ\s]{3,}$/', $nombre)) {
        $mensaje = "El nombre debe contener solo letras y al menos 3 caracteres.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "El formato del correo electrónico no es válido.";
    } elseif (!preg_match('/^\d{9,11}$/', $telefono)) {
        $mensaje = "El teléfono debe tener solo números y entre 9 y 11 dígitos.";
    } else {
        $update_usuario = pg_query_params($conn,
            "UPDATE usuario SET username = $1 WHERE id_usuario = $2",
            array($username, $id_usuario)
        );

        $update_huesped = pg_query_params($conn,
            "UPDATE huesped SET nombre = $1, email = $2, telefono = $3 WHERE id_usuario = $4",
            array($nombre, $email, $telefono, $id_usuario)
        );

        $mensaje = ($update_usuario && $update_huesped)
            ? "Datos actualizados correctamente."
            : "Error al actualizar los datos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Huésped</title>
    <link rel="stylesheet" href="../../../../css/CRUD/style_crud_update.css">
</head>
<body>
<div class="form-container">
    <h2>Editar Huésped</h2>

    <?php if (!empty($mensaje)): ?>
        <div class="mensaje"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="username">Usuario:</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($huesped['username']) ?>" required>
        </div>

        <div class="form-group">
            <label for="nombre">Nombre completo:</label>
            <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($huesped['nombre']) ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Correo electrónico:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($huesped['email']) ?>" required>
        </div>

        <div class="form-group">
            <label for="telefono">Teléfono:</label>
            <input type="text" id="telefono" name="telefono" value="<?= htmlspecialchars($huesped['telefono']) ?>" required>
        </div>

        <div class="form-buttons">
            <button type="submit">Guardar Cambios</button>
            <a href="index.php" class="btn-volver">Volver</a>
        </div>
    </form>
</div>
</body>
</html>
