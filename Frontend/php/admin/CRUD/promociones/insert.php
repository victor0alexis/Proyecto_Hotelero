<?php
include("../../../conexion.php");
session_start();

if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

$mensaje = "";
$titulo = $descripcion = $descuento = $fecha_inicio = $fecha_fin = $estado = "";
$nombre_imagen = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $titulo = trim($_POST["titulo"]);
    $descripcion = trim($_POST["descripcion"]);
    $descuento = intval($_POST["descuento"]);
    $fecha_inicio = $_POST["fecha_inicio"];
    $fecha_fin = $_POST["fecha_fin"];
    $estado = $_POST["estado"] ?? 'activa';

    // Procesar imagen
    if (isset($_FILES["imagen_subida"]) && $_FILES["imagen_subida"]["error"] === 0) {
        $directorio = "../../../../img/promociones/";
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

    // Validaciones
    if (empty($titulo) || empty($descripcion) || empty($descuento) || empty($fecha_inicio) || empty($fecha_fin)) {
        $mensaje = "Todos los campos son obligatorios.";
    } elseif ($descuento < 1 || $descuento > 100) {
        $mensaje = "El descuento debe estar entre 1% y 100%.";
    } elseif (strtotime($fecha_fin) < strtotime($fecha_inicio)) {
        $mensaje = "La fecha de fin no puede ser anterior a la fecha de inicio.";
    } elseif (strlen($titulo) > 100) {
        $mensaje = "El título no debe exceder los 100 caracteres.";
    } elseif (strlen($descripcion) > 500) {
        $mensaje = "La descripción no debe exceder los 500 caracteres.";
    }

    if (empty($mensaje)) {
        $query = pg_query_params($conn, "
            INSERT INTO Promocion (Titulo, Descripcion, Descuento, Fecha_Inicio, Fecha_Fin, Estado, Imagen)
            VALUES ($1, $2, $3, $4, $5, $6, $7)
        ", array($titulo, $descripcion, $descuento, $fecha_inicio, $fecha_fin, $estado, $nombre_imagen));

        if ($query) {
            header("Location: index.php?mensaje=Promoción+creada+correctamente");
            exit();
        } else {
            $mensaje = "Error al registrar la promoción.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Promoción</title>
    <link rel="stylesheet" href="../../../../css/CRUD/style_crud_insert.css">
</head>
<body>
<div class="form-container">
    <h2>Registrar Promoción</h2>

    <?php if ($mensaje): ?>
        <div class="mensaje"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="titulo">Título:</label>
            <input type="text" name="titulo" maxlength="100" required value="<?= htmlspecialchars($titulo) ?>">
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción:</label>
            <textarea name="descripcion" maxlength="500" required><?= htmlspecialchars($descripcion) ?></textarea>
        </div>

        <div class="form-group">
            <label for="descuento">Descuento (%):</label>
            <input type="number" name="descuento" min="1" max="100" required value="<?= htmlspecialchars($descuento) ?>">
        </div>

        <div class="form-group">
            <label for="fecha_inicio">Fecha de inicio:</label>
            <input type="date" name="fecha_inicio" required value="<?= htmlspecialchars($fecha_inicio) ?>">
        </div>

        <div class="form-group">
            <label for="fecha_fin">Fecha de fin:</label>
            <input type="date" name="fecha_fin" required value="<?= htmlspecialchars($fecha_fin) ?>">
        </div>

        <div class="form-group">
            <label for="estado">Estado:</label>
            <select name="estado" required>
                <option value="activa" <?= $estado === 'activa' ? 'selected' : '' ?>>Activa</option>
                <option value="inactiva" <?= $estado === 'inactiva' ? 'selected' : '' ?>>Inactiva</option>
            </select>
        </div>

        <div class="form-group">
            <label for="imagen_subida">Imagen (opcional):</label>
            <input type="file" name="imagen_subida" accept="image/*">
        </div>

        <div class="form-buttons">
            <button type="submit">Registrar</button>
            <a href="index.php" class="btn-volver">Volver</a>
        </div>
    </form>
</div>
</body>
</html>
