<?php
include("../../../conexion.php");
session_start();

if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

$mensaje = "";

$hoteles_query = pg_query($conn, "SELECT id_hotel, nombre FROM hotel");

// Variables para mantener valores en caso de error
$precio = $estado = $tipo = $descripcion = "";
$capacidad = 1;
$id_hotel = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $precio = trim($_POST["precio"]);
    $estado = trim($_POST["estado"]);
    $tipo = trim($_POST["tipo"]);
    $descripcion = trim($_POST["descripcion"]);
    $capacidad = intval($_POST["capacidad"]);
    $id_hotel = trim($_POST["id_hotel"]);
    $nombre_imagen = null;

    // Procesar imagen si se sube
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
        $estados_validos = ['Disponible', 'Ocupada', 'Mantenimiento'];

        // Validaciones
        if (empty($precio) || empty($estado) || empty($tipo) || empty($descripcion) || empty($capacidad) || empty($id_hotel)) {
            $mensaje = "Todos los campos son obligatorios.";
        } elseif (!ctype_digit($precio) || intval($precio) <= 0) {
            $mensaje = "El campo 'Precio' debe ser un número entero positivo (sin decimales).";
        } elseif (intval($precio) % 1000 !== 0) {
            $mensaje = "El campo 'Precio' debe ser múltiplo de 1000.";
        } elseif (!in_array($estado, $estados_validos)) {
            $mensaje = "El estado seleccionado no es válido.";
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
            // Verificar existencia del hotel
            $verificar_hotel = pg_query_params($conn, "SELECT 1 FROM hotel WHERE id_hotel = $1", array($id_hotel));
            if (pg_num_rows($verificar_hotel) === 0) {
                $mensaje = "El hotel seleccionado no existe.";
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
            <label for="precio">Precio (entero múltiplo de 1000):</label>
            <input type="text" name="precio" required value="<?= htmlspecialchars($precio) ?>">
        </div>

        <div class="form-group">
            <label for="estado">Estado:</label>
            <select name="estado" required>
                <option value="">-- Seleccionar Estado --</option>
                <option value="Disponible" <?= $estado === 'Disponible' ? 'selected' : '' ?>>Disponible</option>
                <option value="Ocupada" <?= $estado === 'Ocupada' ? 'selected' : '' ?>>Ocupada</option>
                <option value="Mantenimiento" <?= $estado === 'Mantenimiento' ? 'selected' : '' ?>>Mantenimiento</option>
            </select>
        </div>

        <div class="form-group">
            <label for="tipo">Tipo (máx. 50 caracteres):</label>
            <input type="text" name="tipo" maxlength="50" required value="<?= htmlspecialchars($tipo) ?>">
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción (máx. 200 caracteres):</label>
            <textarea name="descripcion" maxlength="200" required><?= htmlspecialchars($descripcion) ?></textarea>
        </div>

        <div class="form-group">
            <label for="imagen_subida">Subir Imagen (opcional):</label>
            <input type="file" name="imagen_subida" accept="image/*">
        </div>

        <div class="form-group">
            <label for="capacidad">Capacidad:</label>
            <input type="number" name="capacidad" value="<?= htmlspecialchars($capacidad) ?>" min="1" required>
        </div>

        <div class="form-group">
            <label for="id_hotel">Hotel:</label>
            <select name="id_hotel" required>
                <option value="">-- Seleccionar Hotel --</option>
                <?php while ($hotel = pg_fetch_assoc($hoteles_query)): ?>
                    <option value="<?= $hotel['id_hotel'] ?>" <?= $hotel['id_hotel'] == $id_hotel ? 'selected' : '' ?>>
                        <?= htmlspecialchars($hotel['nombre']) ?>
                    </option>
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
