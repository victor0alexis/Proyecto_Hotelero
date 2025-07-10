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
    $estado_actividad = trim($_POST['estado_actividad']);

    // Persistencia
    $habitacion['precio'] = $precio;
    $habitacion['estado'] = $estado;
    $habitacion['tipo'] = $tipo;
    $habitacion['descripcion'] = $descripcion;
    $habitacion['capacidad'] = $capacidad;
    $habitacion['id_hotel'] = $id_hotel;
    $habitacion['estado_actividad'] = $estado_actividad;

    $imagen_final = $habitacion['imagen'];

    if (isset($_FILES['nueva_imagen']) && $_FILES['nueva_imagen']['error'] === UPLOAD_ERR_OK) {
        $nombre_archivo = basename($_FILES['nueva_imagen']['name']);
        $directorio = $_SERVER['DOCUMENT_ROOT'] . "/Proyecto_Hotelero/Frontend/img/habitaciones/";
        $ruta_destino = $directorio . $nombre_archivo;

        if (move_uploaded_file($_FILES['nueva_imagen']['tmp_name'], $ruta_destino)) {
            $imagen_final = $nombre_archivo;
        } else {
            $mensaje = "Error al subir la nueva imagen.";
        }
    }

    $estados_validos = ['Disponible', 'Ocupada', 'Mantenimiento'];
    $actividades_validas = ['activo', 'inactivo'];

    if (empty($precio) || empty($estado) || empty($tipo) || empty($descripcion) || empty($capacidad) || empty($id_hotel) || empty($estado_actividad)) {
        $mensaje = "Todos los campos son obligatorios.";
    } elseif (!ctype_digit($precio) || intval($precio) <= 0) {
        $mensaje = "El campo 'Precio' debe ser un número entero positivo (sin decimales).";
    } elseif (intval($precio) % 1000 !== 0) {
        $mensaje = "El campo 'Precio' debe ser múltiplo de 1000.";
    } elseif (!in_array($estado, $estados_validos)) {
        $mensaje = "El estado seleccionado no es válido.";
    } elseif (!in_array($estado_actividad, $actividades_validas)) {
        $mensaje = "El estado de actividad seleccionado no es válido.";
    } elseif (!preg_match('/^[\p{L}\d\s\-]+$/u', $tipo)) {
        $mensaje = "El campo 'Tipo' solo puede contener letras, números, espacios y guiones.";
    } elseif (strlen($tipo) > 50) {
        $mensaje = "El campo 'Tipo' no debe exceder los 50 caracteres.";
    } elseif (strlen(trim($descripcion)) === 0) {
        $mensaje = "La descripción no puede estar vacía o tener solo espacios.";
    } elseif (strlen($descripcion) > 200) {
        $mensaje = "La descripción no puede superar los 200 caracteres.";
    } elseif ($capacidad < 1) {
        $mensaje = "La 'Capacidad' debe ser al menos 1.";
    } else {
        $update = pg_query_params($conn, "
            UPDATE habitacion
            SET precio = $1, estado = $2, tipo = $3, descripcion = $4, imagen = $5, capacidad = $6, id_hotel = $7, estado_actividad = $8
            WHERE id_habitacion = $9
        ", array($precio, $estado, $tipo, $descripcion, $imagen_final, $capacidad, $id_hotel, $estado_actividad, $id_habitacion));

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
            <label for="estado_actividad">Estado Actividad:</label>
            <select name="estado_actividad" required>
                <option value="activo" <?= $habitacion['estado_actividad'] === 'activo' ? 'selected' : '' ?>>Activo</option>
                <option value="inactivo" <?= $habitacion['estado_actividad'] === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>

        <div class="form-group">
            <label for="tipo">Tipo:</label>
            <input type="text" name="tipo" maxlength="50" value="<?= htmlspecialchars($habitacion['tipo']) ?>" required>
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción:</label>
            <textarea name="descripcion" maxlength="200" required><?= htmlspecialchars($habitacion['descripcion']) ?></textarea>
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
