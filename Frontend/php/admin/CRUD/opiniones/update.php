<?php
include("../../../conexion.php");
session_start();

if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: index.php");
    exit();
}

// Obtener datos actuales
$result = pg_query($conn, "SELECT * FROM opinion WHERE id_opinion = $id");
$opinion = pg_fetch_assoc($result);

// Obtener huéspedes para seleccionar
$huespedes = pg_query($conn, "SELECT id_huesped, nombre FROM huesped ORDER BY nombre ASC");

// Guardar cambios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_huesped = $_POST['id_huesped'];
    $comentario = $_POST['comentario'];
    $clasificacion = $_POST['clasificacion'];

    $query = pg_query($conn, "
        UPDATE opinion
        SET id_huesped = $id_huesped,
            comentario = '$comentario',
            clasificacion = $clasificacion
        WHERE id_opinion = $id
    ");

    if ($query) {
        header("Location: index.php");
        exit();
    } else {
        echo "Error al actualizar.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Opinión</title>
    <link rel="stylesheet" href="../../../../css/CRUD/style_crud_update.css">
</head>
<body>

<h2>Editar Opinión</h2>
<form method="post">
    <label for="id_huesped">Huésped:</label>
    <select name="id_huesped" required>
        <?php while ($h = pg_fetch_assoc($huespedes)): ?>
            <option value="<?= $h['id_huesped'] ?>" <?= $h['id_huesped'] == $opinion['id_huesped'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($h['nombre']) ?>
            </option>
        <?php endwhile; ?>
    </select>

    <label for="comentario">Comentario:</label>
    <textarea name="comentario" required><?= htmlspecialchars($opinion['comentario']) ?></textarea>

    <label for="clasificacion">Calificación (1 a 5):</label>
    <input type="number" name="clasificacion" min="1" max="5" value="<?= $opinion['clasificacion'] ?>" required>

    <input type="submit" value="Actualizar Opinión">
</form>

<a href="index.php">← Volver</a>

</body>
</html>
