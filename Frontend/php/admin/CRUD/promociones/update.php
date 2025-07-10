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

$id_promocion = intval($_GET['id']);

$consulta = pg_query_params($conn, "SELECT * FROM promocion WHERE id_promocion = $1", [$id_promocion]);
$promocion = pg_fetch_assoc($consulta);

if (!$promocion) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST["titulo"]);
    $descripcion = trim($_POST["descripcion"]);
    $descuento = trim($_POST["descuento"]);
    $fecha_inicio = $_POST["fecha_inicio"];
    $fecha_fin = $_POST["fecha_fin"];

    // Mantener datos anteriores si falla algo
    $promocion['titulo'] = $titulo;
    $promocion['descripcion'] = $descripcion;
    $promocion['descuento'] = $descuento;
    $promocion['fecha_inicio'] = $fecha_inicio;
    $promocion['fecha_fin'] = $fecha_fin;

    $imagen_final = $promocion['imagen'];

    if (isset($_FILES['nueva_imagen']) && $_FILES['nueva_imagen']['error'] === UPLOAD_ERR_OK) {
        $nombre_archivo = basename($_FILES['nueva_imagen']['name']);
        $directorio = $_SERVER['DOCUMENT_ROOT'] . "/Proyecto_Hotelero/Frontend/img/promociones/";
        $ruta_destino = $directorio . $nombre_archivo;

        if (move_uploaded_file($_FILES['nueva_imagen']['tmp_name'], $ruta_destino)) {
            $imagen_final = $nombre_archivo;
        } else {
            $mensaje = "Error al subir la nueva imagen.";
        }
    }

    // Validaciones
    if (
        empty($titulo) || empty($descripcion) || empty($descuento) ||
        empty($fecha_inicio) || empty($fecha_fin)
    ) {
        $mensaje = "Todos los campos son obligatorios.";
    } elseif (!ctype_digit($descuento) || $descuento < 0 || $descuento > 100) {
        $mensaje = "El campo 'Descuento' debe ser un número entre 0 y 100.";
    } elseif ($fecha_fin < $fecha_inicio) {
        $mensaje = "La fecha de fin debe ser igual o posterior a la fecha de inicio.";
    } else {
        $update = pg_query_params($conn, "
            UPDATE promocion
            SET titulo = $1, descripcion = $2, descuento = $3,
                fecha_inicio = $4, fecha_fin = $5, imagen = $6
            WHERE id_promocion = $7
        ", [$titulo, $descripcion, $descuento, $fecha_inicio, $fecha_fin, $imagen_final, $id_promocion]);

        if ($update) {
            $mensaje = "Promoción actualizada correctamente.";
            $promocion['imagen'] = $imagen_final;
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
    <title>Editar Promoción</title>
    <link rel="stylesheet" href="../../../../css/CRUD/style_crud_update.css">
</head>
<body>
<div class="form-container">
    <h2>Editar Promoción</h2>

    <?php if (!empty($mensaje)): ?>
        <div class="mensaje"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="titulo">Título:</label>
            <input type="text" name="titulo" maxlength="100" required value="<?= htmlspecialchars($promocion['titulo']) ?>">
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción:</label>
            <textarea name="descripcion" maxlength="500" required><?= htmlspecialchars($promocion['descripcion']) ?></textarea>
        </div>

        <div class="form-group">
            <label for="descuento">Descuento (%):</label>
            <input type="number" name="descuento" min="0" max="100" required value="<?= htmlspecialchars($promocion['descuento']) ?>">
        </div>

        <div class="form-group">
            <label for="fecha_inicio">Fecha de Inicio:</label>
            <input type="date" name="fecha_inicio" required value="<?= htmlspecialchars($promocion['fecha_inicio']) ?>">
        </div>

        <div class="form-group">
            <label for="fecha_fin">Fecha de Fin:</label>
            <input type="date" name="fecha_fin" required value="<?= htmlspecialchars($promocion['fecha_fin']) ?>">
        </div>

        <div class="form-group">
            <label>Imagen actual:</label>
            <?php if (!empty($promocion['imagen'])): ?>
                <br><img src="/Proyecto_Hotelero/Frontend/img/promociones/<?= htmlspecialchars($promocion['imagen']) ?>" width="150">
            <?php else: ?>
                <p>No hay imagen.</p>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="nueva_imagen">Subir nueva imagen (opcional):</label>
            <input type="file" name="nueva_imagen" accept="image/*">
        </div>

        <div class="form-buttons">
            <button type="submit">Guardar Cambios</button>
            <a href="index.php" class="btn-volver">Volver</a>
        </div>
    </form>
</div>
</body>
</html>
