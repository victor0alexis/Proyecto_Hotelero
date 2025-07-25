<?php
include("../../../conexion.php");
session_start();

// Verificar sesión de administrador
if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

// Obtener ID
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id_usuario = intval($_GET['id']);

// Obtener datos actuales
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

// Valores actuales
$username = $huesped['username'];
$nombre = $huesped['nombre'];
$email = $huesped['email'];
$telefono = $huesped['telefono'];

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);

    // Validaciones obligatorias
    if (empty($username)) {
        $errores[] = "El nombre de usuario es obligatorio.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
        $errores[] = "El nombre de usuario debe tener entre 3 y 50 caracteres alfanuméricos.";
    }

    if (empty($nombre)) {
        $errores[] = "El nombre es obligatorio.";
    } elseif (!preg_match('/^[[:alpha:] ]+$/u', $nombre)) {
        $errores[] = "El nombre solo debe contener letras y espacios.";
    }

    if (empty($email)) {
        $errores[] = "El correo electrónico es obligatorio.";
    } elseif (!preg_match('/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/', $email)) {
        $errores[] = "Formato de correo electrónico inválido.";
    }

    if (empty($telefono)) {
        $errores[] = "El teléfono es obligatorio.";
    } elseif (!preg_match('/^[0-9]{7,10}$/', $telefono)) {
        $errores[] = "El teléfono debe tener entre 7 y 10 dígitos numéricos.";
    }

    // Verificar duplicado de usuario
    $verificar = pg_query_params($conn, "
        SELECT id_usuario FROM usuario WHERE username = $1 AND id_usuario != $2
    ", array($username, $id_usuario));
    if (pg_num_rows($verificar) > 0) {
        $errores[] = "El nombre de usuario ya está en uso por otro usuario.";
    }

    if (empty($errores)) {
        $update_usuario = pg_query_params($conn,
            "UPDATE usuario SET username = $1 WHERE id_usuario = $2",
            array($username, $id_usuario)
        );

        $update_huesped = pg_query_params($conn,
            "UPDATE huesped SET nombre = $1, email = $2, telefono = $3 WHERE id_usuario = $4",
            array($nombre, $email, $telefono, $id_usuario)
        );

        if ($update_usuario && $update_huesped) {
            header("Location: index.php?mensaje=Huésped+actualizado+correctamente");
            exit();
        } else {
            $errores[] = "Error al actualizar los datos en la base de datos.";
        }
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

    <?php if (!empty($errores)): ?>
        <div class="mensaje">
            <ul>
                <?php foreach ($errores as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="username">Usuario:</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" required>
        </div>

        <div class="form-group">
            <label for="nombre">Nombre completo:</label>
            <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($nombre) ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Correo electrónico:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
        </div>

        <div class="form-group">
            <label for="telefono">Teléfono:</label>
            <input type="text" id="telefono" name="telefono" value="<?= htmlspecialchars($telefono) ?>" required>
        </div>

        <div class="form-buttons">
            <button type="submit">Guardar Cambios</button>
            <a href="index.php" class="btn-volver">Volver</a>
        </div>
    </form>
</div>
</body>
</html>
