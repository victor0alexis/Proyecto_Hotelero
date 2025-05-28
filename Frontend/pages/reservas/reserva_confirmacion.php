<?php
session_start();
include("../../php/conexion.php");

// Verifica si el usuario está autenticado como huésped
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'huesped') {
    header("Location: ../../php/login/login.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'] ?? null;

if (!$id_usuario) {
    echo "<p>Sesión inválida.</p>";
    exit();
}

// Obtener la reserva más reciente del huésped
$consulta = pg_query_params($conn, "
    SELECT r.*, h.tipo, h.precio, h.descripcion, h.imagen, hu.nombre, hu.email
    FROM reserva r
    JOIN huesped hu ON r.id_huesped = hu.id_huesped
    JOIN habitacion h ON r.id_habitacion = h.id_habitacion
    WHERE hu.id_usuario = $1
    ORDER BY r.id_reserva DESC LIMIT 1
", [$id_usuario]);

if (!$consulta || pg_num_rows($consulta) === 0) {
    echo "<p>No se encontró ninguna reserva reciente.</p>";
    exit();
}

$reserva = pg_fetch_assoc($consulta);

// Calcular total de noches y valor base
$fecha_inicio = new DateTime($reserva['fecha_entrada']);
$fecha_salida = new DateTime($reserva['fecha_salida']);
$noches = $fecha_inicio->diff($fecha_salida)->days;
$total_base = $reserva['precio'] * $noches;

// Consulta de servicios incluidos
$consulta_servicios = pg_query_params($conn, "
    SELECT 
        si.Tipo_Servicio AS tipo_servicio,
        si.Personal_Encargado AS personal_encargado,
        COALESCE(sl.Descripcion, sh.Descripcion, st.Descripcion) AS descripcion,
        COALESCE(sl.Costo, sh.Costo, st.Costo) AS costo
    FROM Servicio_Incluido si
    LEFT JOIN Servicio_Lavanderia sl ON si.Tipo_Servicio = 'lavanderia' AND si.ID_Servicio = sl.ID_Servicio_Lavanderia
    LEFT JOIN Servicio_Habitacion sh ON si.Tipo_Servicio = 'habitacion' AND si.ID_Servicio = sh.ID_Servicio_Habitacion
    LEFT JOIN Servicio_Transporte st ON si.Tipo_Servicio = 'transporte' AND si.ID_Servicio = st.ID_Servicio_Transporte
    WHERE si.ID_Habitacion = $1
", [$reserva['id_habitacion']]);

$servicios = [];
$total_servicios = 0;

while ($row = pg_fetch_assoc($consulta_servicios)) {
    $servicios[] = $row;
    $total_servicios += floatval($row['costo']);
}

// Total final incluyendo servicios
$total = $total_base + $total_servicios;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Confirmación de Reserva</title>
    <link rel="stylesheet" href="../../css/Reserva/style_reserva_confirmacion.css">
</head>
<body>

<div class="confirmacion-container">
    <h1>Reserva Confirmada</h1>

    <div class="resumen">
        <div class="seccion detalle-habitacion">
            <img src="../../img/habitaciones/<?= htmlspecialchars($reserva['imagen']) ?>" 
                 alt="Imagen de la habitación <?= htmlspecialchars($reserva['tipo']) ?>" 
                 class="habitacion-img">
            <h2><?= htmlspecialchars($reserva['tipo']) ?></h2>
            <p><strong>Precio por noche:</strong> $<?= number_format($reserva['precio'], 3) ?></p>
            <p><?= htmlspecialchars($reserva['descripcion']) ?></p>
        </div>

        <?php if (!empty($servicios)): ?>
            <div class="seccion detalle-huesped">
                <h3>Servicios Incluidos</h3>
                <ul>
                    <?php foreach ($servicios as $serv): ?>
                        <li>
                            <strong><?= ucfirst(htmlspecialchars($serv['tipo_servicio'])) ?>:</strong>
                            <?= htmlspecialchars($serv['descripcion']) ?> —
                            Atendido por <?= htmlspecialchars($serv['personal_encargado']) ?> —
                            <span>Costo: $<?= number_format($serv['costo'], 3) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p><strong>Total Servicios:</strong> $<?= number_format($total_servicios, 3) ?></p>
            </div>
        <?php endif; ?>

        <div class="seccion detalle-huesped">
            <h3>Datos del Huésped</h3>
            <p><strong>Nombre:</strong> <?= htmlspecialchars($reserva['nombre']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($reserva['email']) ?></p>
        </div>

        <div class="seccion detalle-reserva">
            <h3>Detalles de la Reserva</h3>
            <p><strong>Fecha de Entrada:</strong> <?= htmlspecialchars($reserva['fecha_entrada']) ?></p>
            <p><strong>Fecha de Salida:</strong> <?= htmlspecialchars($reserva['fecha_salida']) ?></p>
            <p><strong>Noches:</strong> <?= $noches ?></p>
            <p><strong>Total por Noches:</strong> $<?= number_format($total_base, 3) ?></p>
            <p><strong>Total Servicios:</strong> $<?= number_format($total_servicios, 3) ?></p>
            <p><strong>Total a Pagar:</strong> $<?= number_format($total, 3) ?></p>
            <p><strong>Estado:</strong> <?= ucfirst(htmlspecialchars($reserva['estado'])) ?></p>
        </div>
    </div>

    <div class="botones">
        <?php if ($reserva['estado'] === 'pendiente'): ?>
    <a href="pago_reserva.php?id=<?= $reserva['id_reserva'] ?>" class="btn">Pagar Ahora</a>
        <?php endif ?>
        
        <?php if ($reserva['estado'] === 'confirmada'): ?>
    <a href="../../php/huesped/reservas_hechas.php" class="btn">← Volver a Mis Reservas</a>
        <?php endif ?>
        
        <a href="../index.php" class="btn">Volver a Pagina Principal</a>
    </div>
</div>

</body>
</html>
