<?php
session_start();
include("../conexion.php");

// Verificación de sesión de huésped
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'huesped') {
    header("Location: ../login/login.php");
    exit();
}

$id_huesped = $_SESSION['id_huesped'] ?? null;

if (!$id_huesped) {
    echo "<p>Sesión inválida: ID del huésped no disponible.</p>";
    exit();
}

// Validar y obtener id_reserva desde la URL
$id_reserva = $_GET['id_reserva'] ?? null;
if (!$id_reserva || !is_numeric($id_reserva)) {
    echo "<p>ID de reserva inválido.</p>";
    exit();
}

// Consulta para verificar si la reserva pertenece al huésped
$verificar = pg_query_params($conn, "
    SELECT r.id_reserva 
    FROM reserva r
    WHERE r.id_reserva = $1 AND r.id_huesped = $2
", [$id_reserva, $id_huesped]);

if (pg_num_rows($verificar) === 0) {
    echo "<p>Reserva no encontrada o no pertenece a este huésped.</p>";
    exit();
}

// Consultar datos de la boleta y reserva
$consulta = pg_query_params($conn, "
SELECT 
    r.id_reserva, r.fecha_entrada, r.fecha_salida, r.estado AS estado_reserva,
    h.tipo AS tipo_habitacion, h.descripcion, h.precio, h.capacidad,
    b.id_boleta, b.monto, b.estado_pago, b.fecha_pago,
    mp.nombre_metodo, mp.numero_operacion,
    hu.nombre AS nombre_huesped, hu.email, hu.telefono
FROM reserva r
JOIN habitacion h ON r.id_habitacion = h.id_habitacion
LEFT JOIN boleta b ON b.id_reserva = r.id_reserva
LEFT JOIN metodo_pago mp ON mp.id_boleta = b.id_boleta
JOIN huesped hu ON r.id_huesped = hu.id_huesped
WHERE r.id_reserva = $1
", [$id_reserva]);

$datos = pg_fetch_assoc($consulta);

// Consultar servicios asociados a la boleta
$servicios = pg_query_params($conn, "
SELECT si.tipo_servicio, si.personal_encargado, 
    COALESCE(st.descripcion, sl.descripcion, sh.descripcion) AS descripcion,
    COALESCE(st.costo, sl.costo, sh.costo) AS costo
FROM boleta_servicio bs
JOIN servicio_incluido si ON si.id_servicio_incluido = bs.id_servicio_incluido
LEFT JOIN servicio_transporte st ON st.id_servicio_transporte = si.id_servicio
LEFT JOIN servicio_lavanderia sl ON sl.id_servicio_lavanderia = si.id_servicio
LEFT JOIN servicio_habitacion sh ON sh.id_servicio_habitacion = si.id_servicio
WHERE bs.id_boleta = $1
", [$datos['id_boleta']]);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Boleta de Reserva</title>
    <link rel="stylesheet" href="../../css/Reserva/style_boleta.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    
<div class="boleta-container">
    <div class="boleta-header">
        <h1>Boleta de Reserva</h1>
        <p>Hotel  - Tu descanso, nuestra prioridad</p>
    </div>

    <div class="boleta-section">
        <h2><i class="fa-solid fa-calendar-check"></i> Datos de la Reserva</h2>
        <p><i class="fa-solid fa-hashtag icon"></i><strong>Reserva #:</strong> <?= htmlspecialchars($datos['id_reserva']) ?></p>
        <p><i class="fa-solid fa-user icon"></i><strong>Huésped:</strong> <?= htmlspecialchars($datos['nombre_huesped']) ?> (<?= htmlspecialchars($datos['email']) ?>)</p>
        <p><i class="fa-solid fa-phone icon"></i><strong>Teléfono:</strong> <?= htmlspecialchars($datos['telefono']) ?></p>
        <p><i class="fa-solid fa-bed icon"></i><strong>Habitación:</strong> <?= htmlspecialchars($datos['tipo_habitacion']) ?> - <?= htmlspecialchars($datos['descripcion']) ?></p>
        <p><i class="fa-solid fa-users icon"></i><strong>Capacidad:</strong> <?= htmlspecialchars($datos['capacidad']) ?> persona(s)</p>
        <p><i class="fa-solid fa-dollar-sign icon"></i><strong>Precio por noche:</strong> $<?= number_format($datos['precio'], 3) ?></p>
        <p><i class="fa-solid fa-calendar-days icon"></i><strong>Entrada:</strong> <?= htmlspecialchars($datos['fecha_entrada']) ?></p>
        <p><i class="fa-solid fa-calendar-day icon"></i><strong>Salida:</strong> <?= htmlspecialchars($datos['fecha_salida']) ?></p>
        <p><i class="fa-solid fa-flag icon"></i><strong>Estado Reserva:</strong> <?= htmlspecialchars($datos['estado_reserva']) ?></p>
    </div>

    <div class="boleta-section">
        <h2><i class="fa-solid fa-file-invoice-dollar"></i> Detalles de Boleta</h2>
        <?php if ($datos['id_boleta']): ?>
            <p><i class="fa-solid fa-receipt icon"></i><strong>ID Boleta:</strong> <?= htmlspecialchars($datos['id_boleta']) ?></p>
            <p><i class="fa-solid fa-money-bill-wave icon"></i><strong>Monto Total:</strong> $<?= number_format($datos['monto'], 3) ?></p>
            <p><i class="fa-solid fa-calendar icon"></i><strong>Fecha de Pago:</strong> <?= htmlspecialchars($datos['fecha_pago']) ?></p>
            <p><i class="fa-solid fa-circle-check icon"></i><strong>Estado de Pago:</strong> <?= htmlspecialchars($datos['estado_pago']) ?></p>
            <p><i class="fa-solid fa-credit-card icon"></i><strong>Método de Pago:</strong> <?= htmlspecialchars($datos['nombre_metodo']) ?> (<?= htmlspecialchars($datos['numero_operacion']) ?>)</p>
        <?php else: ?>
            <p><i class="fa-solid fa-circle-exclamation icon"></i> No hay boleta generada para esta reserva.</p>
        <?php endif; ?>
    </div>

    <div class="boleta-section">
        <h2><i class="fa-solid fa-concierge-bell"></i> Servicios Incluidos</h2>
        <?php if (pg_num_rows($servicios) > 0): ?>
            <ul>
                <?php while ($s = pg_fetch_assoc($servicios)): ?>
                    <li>
                        <i class="fa-solid fa-circle-check icon"></i> <?= ucfirst(htmlspecialchars($s['tipo_servicio'])) ?> - <?= htmlspecialchars($s['descripcion']) ?> ($<?= number_format($s['costo'], 3) ?>) - Atendido por: <?= htmlspecialchars($s['personal_encargado']) ?>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p><i class="fa-solid fa-ban icon"></i> No hay servicios adicionales registrados.</p>
        <?php endif; ?>
    </div>

    <div class="boleta-total">
        <p><i class="fa-solid fa-thumbs-up icon"></i> ¡Gracias por elegirnos! Esperamos verte pronto.</p>
    </div>

    <a href="reservas_hechas.php" class="btn-regresar"><i class="fa-solid fa-arrow-left"></i> Volver a Mis Reservas</a>
</div>


</body>
</html>
