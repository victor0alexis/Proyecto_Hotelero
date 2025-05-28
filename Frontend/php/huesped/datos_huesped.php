<?php
include("../conexion.php");
session_start();

// Verifica si está autenticado como huésped
if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'huesped') {
    header("Location: ../../login/login.php");
    exit();
}

$username = $_SESSION['username'];
$mensaje = "";

// Procesar el formulario
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nuevo_username = trim($_POST['username']);
    $nuevo_nombre = trim($_POST['nombre']);
    $nuevo_email = trim($_POST['email']);
    $nuevo_telefono = trim($_POST['telefono']);

    if (!empty($nuevo_username) && !empty($nuevo_nombre) && !empty($nuevo_email) && !empty($nuevo_telefono)) {
        // Obtener id_usuario
        $res_usuario = pg_query_params($conn,
            "SELECT id_usuario FROM usuario WHERE username = $1",
            array($username)
        );
        $fila_usuario = pg_fetch_assoc($res_usuario);

        if ($fila_usuario) {
            $id_usuario = $fila_usuario['id_usuario'];

            // Actualizar usuario (username)
            $update_usuario = pg_query_params($conn,
                "UPDATE usuario SET username = $1 WHERE id_usuario = $2",
                array($nuevo_username, $id_usuario)
            );

            // Actualizar huesped
            $update_huesped = pg_query_params($conn,
                "UPDATE huesped SET nombre = $1, email = $2, telefono = $3 WHERE id_usuario = $4",
                array($nuevo_nombre, $nuevo_email, $nuevo_telefono, $id_usuario)
            );

            if ($update_usuario && $update_huesped) {
                $_SESSION['username'] = $nuevo_username; // Actualiza la sesión
                $mensaje = "Datos actualizados correctamente.";
            } else {
                $mensaje = "Error al actualizar los datos.";
            }
        } else {
            $mensaje = "Usuario no encontrado.";
        }
    } else {
        $mensaje = "Todos los campos son obligatorios.";
    }
}

// Obtener datos actuales
$consulta = pg_query_params($conn,
    "SELECT u.username, h.nombre, h.email, h.telefono
     FROM usuario u
     JOIN huesped h ON u.id_usuario = h.id_usuario
     WHERE u.username = $1",
    array($username)
);

$huesped = pg_fetch_assoc($consulta);

if (!$huesped) {
    header("Location: datos_huesped.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Mis Datos</title>
  <link rel="stylesheet" href="../../css/style_huesped.css">
</head>
<body>

<div class="form-container">
  <h2>Editar Mis Datos</h2>

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
      <a href="../../pages/index.php" class="btn-volver">Volver</a>
    </div>
  </form>
</div>

</body>
</html>
