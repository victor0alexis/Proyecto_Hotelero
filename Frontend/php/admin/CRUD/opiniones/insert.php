<?php
include("../../../conexion.php");
session_start();

if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

// Obtener huéspedes para el selector
$huespedes = pg_query($conn, "SELECT id_huesped, nombre FROM huesped ORDER BY nombre ASC");

// Obtener reservas para el selector
$reservas = pg_query($conn, "
    SELECT r.id_reserva, r.fecha_entrada, r.fecha_salida, h.nombre
    FROM reserva r
    INNER JOIN huesped h ON r.id_huesped = h.id_huesped
    WHERE r.estado = 'confirmada'
    ORDER BY r.id_reserva DESC
");

// Procesar envío del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_huesped = $_POST['id_huesped'];
    $id_reserva = $_POST['id_reserva'];
    $comentario = $_POST['comentario'];
    $clasificacion = $_POST['clasificacion'];
    $fecha = date('Y-m-d'); // Solo fecha

    $query = pg_query_params($conn, "
        INSERT INTO opinion (id_huesped, id_reserva, comentario, clasificacion, fecha)
        VALUES ($1, $2, $3, $4, $5)
    ", array($id_huesped, $id_reserva, $comentario, $clasificacion, $fecha));

    if ($query) {
        header("Location: index.php");
        exit();
    } else {
        echo "Error al insertar opinión.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Opinión</title>
    <link rel="stylesheet" href="../../../../css/CRUD/style_crud_insert.css">
</head>
<body>

<div class="form-container">
<h2>Registrar Opinión</h2>
<form method="post">
    <label for="id_huesped">Huésped:</label>
    <select name="id_huesped" required>
        <option value="">Selecciona un huésped</option>
        <?php while ($h = pg_fetch_assoc($huespedes)): ?>
            <option value="<?= $h['id_huesped'] ?>"><?= htmlspecialchars($h['nombre']) ?></option>
        <?php endwhile; ?>
    </select>

    <label for="id_reserva">Reserva:</label>
    <select name="id_reserva" required>
        <option value="">Selecciona una reserva</option>
        <?php while ($r = pg_fetch_assoc($reservas)): ?>
            <option value="<?= $r['id_reserva'] ?>">
                #<?= $r['id_reserva'] ?> - <?= $r['nombre'] ?> (<?= $r['fecha_entrada'] ?> a <?= $r['fecha_salida'] ?>)
            </option>
        <?php endwhile; ?>
    </select>

    <label for="comentario">Comentario:</label>
    <textarea name="comentario" required></textarea>

    <label for="clasificacion">Calificación (1 a 5):</label>
    <input type="number" name="clasificacion" min="1" max="5" required>

    <div class="form-buttons">
        <button type="submit">Guardar</button>
        <a href="index.php" class="btn-volver">Volver</a>
    </div>
</form>
</div>

</body>
</html>
