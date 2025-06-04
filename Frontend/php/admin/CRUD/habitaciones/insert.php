<?php
include("../../../conexion.php");
session_start();

if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

$mensaje = "";

$hoteles_query = pg_query($conn, "SELECT id_hotel, nombre FROM hotel");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $precio = trim($_POST["precio"]);
    $estado = trim($_POST["estado"]);
    $tipo = trim($_POST["tipo"]);
    $descripcion = trim($_POST["descripcion"]);
    $nombre_manual = trim($_POST["imagen"]);
    $capacidad = intval($_POST["capacidad"]);
    $id_hotel = intval($_POST["id_hotel"]);

    // Procesamiento de imagen subida
    $nombre_imagen = $nombre_manual; // Por defecto

    if (isset($_FILES["imagen_subida"]) && $_FILES["imagen_subida"]["error"] === 0) {
        $directorio = "../../../../img/habitaciones/";
        $nombre_archivo = basename($_FILES["imagen_subida"]["name"]);
        $ruta_destino = $directorio . $nombre_archivo;

        if (getimagesize($_FILES["imagen_subida"]["tmp_name"])) {
            if (move_uploaded_file($_FILES["imagen_subida"]["tmp_name"], $ruta_destino)) {
                $nombre_imagen = $nombre_archivo;
            } else {
                $mensaje = "Error al subir la imagen.";
            }
        } else {
            $mensaje = "El archivo no es una imagen válida.";
        }
    }

    if (empty($mensaje)) {
        if (empty($precio) || empty($estado) || empty($tipo) || empty($id_hotel)) {
            $mensaje = "Los campos 'Precio', 'Estado', 'Tipo' y 'Hotel' son obligatorios.";
        } elseif (!is_numeric($precio)) {
            $mensaje = "El campo 'Precio' debe ser un número válido.";
        } elseif ($capacidad < 1) {
            $mensaje = "La 'Capacidad' debe ser al menos 1.";
        } else {
            $insert = pg_query_params($conn, "
                INSERT INTO habitacion (precio, estado, tipo, descripcion, imagen, capacidad, id_hotel)
                VALUES ($1, $2, $3, $4, $5, $6, $7)
            ", array($precio, $estado, $tipo, $descripcion, $nombre_imagen, $capacidad, $id_hotel));

            if ($insert) {
                header("Location: index.php?mensaje=Habitación+registrada+correctamente");
                exit();
            } else {
                $mensaje = "Error al registrar la habitación.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Habitación</title>
    <link rel="stylesheet" href="../../../../css/CRUD/style_crud_insert.css">
</head>
<body>
<div class="form-container">
    <h2>Registrar Habitación</h2>

    <?php if ($mensaje): ?>
        <div class="mensaje"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="precio">Precio:</label>
            <input type="text" name="precio" required>
        </div>

        <div class="form-group">
            <label for="estado">Estado:</label>
            <select name="estado" required>
                <option value="">-- Seleccionar Estado --</option>
                <option value="Disponible">Disponible</option>
                <option value="Ocupada">Ocupada</option>
                <option value="Mantenimiento">Mantenimiento</option>
            </select>
        </div>

        <div class="form-group">
            <label for="tipo">Tipo:</label>
            <input type="text" name="tipo" required>
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción:</label>
            <textarea name="descripcion"></textarea>
        </div>


        <div class="form-group">
            <label for="imagen_subida">Subir Imagen:</label>
            <input type="file" name="imagen_subida" accept="image/*">
        </div>

        <div class="form-group">
            <label for="capacidad">Capacidad:</label>
            <input type="number" name="capacidad" value="1" min="1" required>
        </div>

        <div class="form-group">
            <label for="id_hotel">Hotel:</label>
            <select name="id_hotel" required>
                <option value="">-- Seleccionar Hotel --</option>
                <?php while ($hotel = pg_fetch_assoc($hoteles_query)): ?>
                    <option value="<?= $hotel['id_hotel'] ?>"><?= htmlspecialchars($hotel['nombre']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-buttons">
            <button type="submit">Registrar</button>
            <a href="index.php" class="btn-volver">Volver</a>
        </div>
    </form>
</div>
</body>
</html>
