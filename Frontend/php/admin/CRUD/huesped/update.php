<?php
include("../../../conexion.php");        //conexion BD.
session_start();                        //Iniciamos session.

// Verificar si hay sesión iniciada como administrador
if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

//Variable para mensajes de error o éxito.
$mensaje = "";

// Verifica que se haya pasado el parámetro id por URL.
if (!isset($_GET['id'])) {
    //si no se proporciona, redirige a "index.php".
    header("Location: index.php");
    exit();
}
//Convierte valor "$_GET['id']" a entero, se almacena en "$id_usuario".
$id_usuario = intval($_GET['id']);

// Obtener datos actuales del huésped, en tablas "huesped", "usuario".
$consulta = pg_query_params($conn, "
    SELECT h.id_huesped, u.id_usuario, u.username, h.nombre, h.email, h.telefono, u.clave
    FROM huesped h
    JOIN usuario u ON h.id_usuario = u.id_usuario
    WHERE u.id_usuario = $1
", array($id_usuario));

//Convierte el resultado en un array asociativo (clave => valor) para acceder fácilmente a los datos.
$huesped = pg_fetch_assoc($consulta);

//Si no se encuentra ningún huésped con el id_usuario proporcionado, redirige a la página principal.
if (!$huesped) {
    header("Location: index.php");
    exit();
}

// Procesar formulario, metodo "POST", .
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Recoge datos del formulario.
    $username = trim($_POST['username']);
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);

    // Verificar si el username ya está en uso por otro usuario
    $verificar_username = pg_query_params($conn, "SELECT id_usuario FROM usuario WHERE username = $1 AND id_usuario != $2", array($username, $id_usuario));
    

    if (pg_num_rows($verificar_username) > 0) {
        $mensaje = "El nombre de usuario ya está en uso por otro usuario.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "El formato del correo electrónico no es válido.";
    } elseif (strlen($telefono) < 7 || strlen($telefono) > 10) {
        $mensaje = "El número de teléfono debe tener entre 7 y 10 caracteres.";
    } else {
        // Actualizar usuario
        $update_usuario = pg_query_params($conn,
            "UPDATE usuario SET username = $1 WHERE id_usuario = $2",
            array($username, $id_usuario)
        );

        // Actualizar huésped
        $update_huesped = pg_query_params($conn,
            "UPDATE huesped SET nombre = $1, email = $2, telefono = $3 WHERE id_usuario = $4",
            array($nombre, $email, $telefono, $id_usuario)
        );

        if ($update_usuario && $update_huesped) {
            $mensaje = "Datos actualizados correctamente.";
        } else {
            $mensaje = "Error al actualizar los datos.";
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
