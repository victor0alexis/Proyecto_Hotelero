<?php
session_start();
include("../../php/conexion.php");

// Obtener ID de habitación primero
if (!isset($_GET['id'])) {
    header("Location: habitaciones.php");
    exit();
}

$id_habitacion = intval($_GET['id']);

// Si no hay sesión de huésped, redirigir al login con URL actual
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'huesped') {
    $url_actual = $_SERVER['REQUEST_URI']; // Incluye /habitaciones/reserva_formulario.php?id=XX
    header("Location: ../../php/login/login.php?redirect=" . urlencode($url_actual));

    exit();
}

// Consultar información de la habitación
$consulta = pg_query_params($conn, "
    SELECT h.ID_Habitacion, h.Tipo, h.Precio, h.Descripcion, h.Imagen
    FROM Habitacion h
    WHERE h.ID_Habitacion = $1
", array($id_habitacion));
$habitacion = pg_fetch_assoc($consulta);

if (!$habitacion) {
    echo "<p>Habitación no encontrada.</p>";
    exit();
}

// Obtener información del huésped
$id_usuario = $_SESSION['id_usuario'];
$query_huesped = pg_query_params($conn, "SELECT nombre, email FROM huesped WHERE id_usuario = $1", array($id_usuario));
$huesped = pg_fetch_assoc($query_huesped);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reservar Habitación</title>
    <link rel="stylesheet" href="../../css/Habitaciones/style_reserva_formulario.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>

<div class="reserva-container">
    <h1>Reserva de Habitación</h1>
    <div class="habitacion-info">
        <img src="../../img/habitaciones/<?= htmlspecialchars($habitacion['imagen']) ?>" alt="Imagen habitación">
        <div>
            <h2><?= htmlspecialchars($habitacion['tipo']) ?></h2>
            <p><strong>Precio:</strong> $<?= number_format($habitacion['precio'], 2) ?></p>
            <p><?= htmlspecialchars($habitacion['descripcion']) ?></p>
        </div>
    </div>

    <div class="huesped-info">
        <h3>Datos del Huésped</h3>
        <p><strong>Nombre:</strong> <?= htmlspecialchars($huesped['nombre']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($huesped['email']) ?></p>
    </div>

    <form action="procesar_reserva.php" method="POST" class="formulario-reserva">
        <input type="hidden" name="id_habitacion" value="<?= $habitacion['id_habitacion'] ?>">

        <label for="fecha_inicio">Fecha de Entrada:</label>
        <input type="date" name="fecha_inicio" required>

        <label for="fecha_fin">Fecha de Salida:</label>
        <input type="date" name="fecha_fin" required>

        <label for="personas">Número de Personas:</label>
        <input type="number" name="personas" min="1" required>

        <button type="submit">Confirmar Reserva</button>
    </form>

    <a href="detalle_habitacion.php?id=<?= $habitacion['id_habitacion'] ?>" class="btn-volver">← Volver</a>
</div>

</body>
</html>
