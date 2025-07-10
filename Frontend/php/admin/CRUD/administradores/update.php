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
    SELECT a.id_admin, u.id_usuario, u.username, a.nombre, a.email
    FROM administrador a
    JOIN usuario u ON a.id_usuario = u.id_usuario
    WHERE u.id_usuario = $1
", array($id_usuario));

$admin = pg_fetch_assoc($consulta);

if (!$admin) {
    header("Location: index.php");
    exit();
}

// Variables actuales
$username = $admin['username'];
$nombre = $admin['nombre'];
$email = $admin['email'];

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);

    // Validaciones
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

    if (!empty($email) && !preg_match('/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/', $email)) {
        $errores[] = "Formato de correo electrónico inválido.";
    }

    // Verificar duplicado de usuario
    $verificar = pg_query_params($conn, "
        SELECT id_usuario FROM usuario WHERE username = $1 AND id_usuario != $2
    ", array($username, $id_usuario));

    if (pg_num_rows($verificar) > 0) {
        $errores[] = "El nombre de usuario ya está en uso por otro usuario.";
    }

    // Actualizar si no hay errores
    if (empty($errores)) {
        $update_usuario = pg_query_params($conn,
            "UPDATE usuario SET username = $1 WHERE id_usuario = $2",
            array($username, $id_usuario)
        );

        $update_admin = pg_query_params($conn,
            "UPDATE administrador SET nombre = $1, email = $2 WHERE id_usuario = $3",
            array($nombre, $email, $id_usuario)
        );

        if ($update_usuario && $update_admin) {
            header("Location: index.php?mensaje=Administrador+actualizado+correctamente");
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
    <title>Editar Administrador</title>
    <link rel="stylesheet" href="../../../../css/CRUD/style_crud_update.css">
</head>
<body>
<div class="form-container">
    <h2>Editar Administrador</h2>

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
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>">
        </div>

        <div class="form-buttons">
            <button type="submit">Guardar Cambios</button>
            <a href="index.php" class="btn-volver">Volver</a>
        </div>
    </form>
</div>
</body>
</html>
