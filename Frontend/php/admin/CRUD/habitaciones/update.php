<?php
include("../../../conexion.php");
session_start();

if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

$mensaje = "";

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id_habitacion = intval($_GET['id']);

$consulta = pg_query_params($conn, "SELECT * FROM habitacion WHERE id_habitacion = $1", array($id_habitacion));
$habitacion = pg_fetch_assoc($consulta);

if (!$habitacion) {
    header("Location: index.php");
    exit();
}

$hoteles_query = pg_query($conn, "SELECT id_hotel, nombre FROM hotel");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $precio = trim($_POST["precio"]);
    $estado = trim($_POST["estado"]);
    $tipo = trim($_POST["tipo"]);
    $descripcion = trim($_POST["descripcion"]);
    $capacidad = intval($_POST["capacidad"]);
    $id_hotel = intval($_POST["id_hotel"]);

    $imagen_final = $habitacion['imagen']; // Por defecto, mantener la actual

    // Si se ha subido una nueva imagen
    if (isset($_FILES['nueva_imagen']) && $_FILES['nueva_imagen']['error'] === UPLOAD_ERR_OK) {
        $nombre_archivo = basename($_FILES['nueva_imagen']['name']);
        $directorio = $_SERVER['DOCUMENT_ROOT'] . "/Proyecto_Hotelero/Frontend/img/habitaciones/";
        $ruta_destino = $directorio . $nombre_archivo;

        if (move_uploaded_file($_FILES['nueva_imagen']['tmp_name'], $ruta_destino)) {
            $imagen_final = $nombre_archivo; // Guardar el nuevo nombre
        } else {
            $mensaje = "Error al subir la nueva imagen.";
        }
    }

    if (empty($precio) || empty($estado) || empty($tipo) || empty($id_hotel)) {
        $mensaje = "Los campos 'Precio', 'Estado', 'Tipo' y 'Hotel' son obligatorios.";
    } elseif (!is_numeric($precio)) {
        $mensaje = "El campo 'Precio' debe ser un número válido.";
    } elseif ($capacidad < 1) {
        $mensaje = "La 'Capacidad' debe ser al menos 1.";
    } elseif (empty($mensaje)) {
        $update = pg_query_params($conn, "
            UPDATE habitacion
            SET precio = $1, estado = $2, tipo = $3, descripcion = $4, imagen = $5, capacidad = $6, id_hotel = $7
            WHERE id_habitacion = $8
        ", array($precio, $estado, $tipo, $descripcion, $imagen_final, $capacidad, $id_hotel, $id_habitacion));

        if ($update) {
            $mensaje = "Datos actualizados correctamente.";
            $habitacion['imagen'] = $imagen_final;
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
    <title>Editar Habitación</title>
    <link rel="stylesheet" href="../../../../css/CRUD/style_crud_update.css">
</head>
<body>
<div class="form-container">
    <h2>Editar Habitación</h2>

    <?php if (!empty($mensaje)): ?>
        <div class="mensaje"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="precio">Precio:</label>
            <input type="text" name="precio" value="<?= htmlspecialchars($habitacion['precio']) ?>" required>
        </div>

        <div class="form-group">
            <label for="estado">Estado:</label>
            <select name="estado" required>
                <option value="Disponible" <?= $habitacion['estado'] === 'Disponible' ? 'selected' : '' ?>>Disponible</option>
                <option value="Ocupada" <?= $habitacion['estado'] === 'Ocupada' ? 'selected' : '' ?>>Ocupada</option>
                <option value="Mantenimiento" <?= $habitacion['estado'] === 'Mantenimiento' ? 'selected' : '' ?>>Mantenimiento</option>
            </select>
        </div>

        <div class="form-group">
            <label for="tipo">Tipo:</label>
            <input type="text" name="tipo" value="<?= htmlspecialchars($habitacion['tipo']) ?>" required>
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción:</label>
            <textarea name="descripcion"><?= htmlspecialchars($habitacion['descripcion']) ?></textarea>
        </div>

        <div class="form-group">
            <label>Imagen actual:</label>
            <?php if (!empty($habitacion['imagen'])): ?>
                <br><img src="/Proyecto_Hotelero/Frontend/img/habitaciones/<?= htmlspecialchars($habitacion['imagen']) ?>" width="150">
            <?php else: ?>
                <p>No hay imagen.</p>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="nueva_imagen">Subir nueva imagen (opcional):</label>
            <input type="file" name="nueva_imagen" accept="image/*">
        </div>

        <div class="form-group">
            <label for="capacidad">Capacidad:</label>
            <input type="number" name="capacidad" value="<?= htmlspecialchars($habitacion['capacidad']) ?>" min="1" required>
        </div>

        <div class="form-group">
            <label for="id_hotel">Hotel:</label>
            <select name="id_hotel" required>
                <?php while ($hotel = pg_fetch_assoc($hoteles_query)): ?>
                    <option value="<?= $hotel['id_hotel'] ?>" <?= $hotel['id_hotel'] == $habitacion['id_hotel'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($hotel['nombre']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-buttons">
            <button type="submit">Guardar Cambios</button>
            <a href="index.php" class="btn-volver">Volver</a>
        </div>
    </form>
</div>
</body>
</html>
