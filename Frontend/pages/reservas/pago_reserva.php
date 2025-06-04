<?php
session_start();
include("../../php/conexion.php");

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'huesped') {
    header("Location: ../../php/login/login.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'] ?? null;
$id_reserva = $_GET['id'] ?? $_POST['id_reserva'] ?? null;

if (!$id_usuario || !$id_reserva) {
    echo "<p>Reserva no especificada.</p>";
    exit();
}

$res = pg_query_params($conn, "
    SELECT r.*, h.tipo, h.precio, h.id_habitacion, hu.nombre 
    FROM reserva r
    JOIN huesped hu ON r.id_huesped = hu.id_huesped
    JOIN habitacion h ON r.id_habitacion = h.id_habitacion
    WHERE r.id_reserva = $1
", [$id_reserva]);

$reserva = pg_fetch_assoc($res);
if (!$reserva || $reserva['estado'] !== 'pendiente') {
    echo "<p>No se puede procesar esta reserva.</p>";
    exit();
}

$inicio = new DateTime($reserva['fecha_entrada']);
$salida = new DateTime($reserva['fecha_salida']);
$noches = $inicio->diff($salida)->days;
$total_hab = $reserva['precio'] * $noches;

$total_serv = 0;
$fecha_actual = date('Y-m-d');
$servicios_incluidos_ids = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    pg_query($conn, "BEGIN");

    foreach ($_SESSION['servicios_temporales'] ?? [] as $servicio) {
        $tipo = $servicio['tipo_servicio'];
        $id_original = $servicio['id_original'];
        $descripcion = $servicio['descripcion'];
        $costo = floatval(str_replace('.', '', $servicio['costo']));
        $personal = $servicio['personal_encargado'];

        $insert_si = pg_query_params($conn, "
            INSERT INTO servicio_incluido (id_servicio, tipo_servicio, personal_encargado, id_habitacion, id_reserva)
            VALUES ($1, $2, $3, $4, $5) RETURNING id_servicio_incluido
        ", [$id_original, $tipo, $personal, $reserva['id_habitacion'], $id_reserva]);

        $row_si = pg_fetch_assoc($insert_si);
        $id_si = $row_si['id_servicio_incluido'];
        $servicios_incluidos_ids[] = $id_si;

        $tabla_servicio = match ($tipo) {
            'transporte' => 'servicio_transporte',
            'lavanderia' => 'servicio_lavanderia',
            'habitacion' => 'servicio_habitacion',
        };

        pg_query_params($conn, "UPDATE $tabla_servicio SET fecha_servicio = $1 WHERE id_servicio_{$tipo} = $2", [$fecha_actual, $id_original]);

        $total_serv += $costo;
    }

    $total_general = $total_hab + $total_serv;

    // Boleta
    $boleta = pg_query_params($conn, "
        INSERT INTO boleta (monto, fecha_pago, estado_pago, id_reserva)
        VALUES ($1, $2, 'pagado', $3) RETURNING id_boleta
    ", [$total_general, $fecha_actual, $id_reserva]);

    $id_boleta = pg_fetch_result($boleta, 0, 'id_boleta');

    // Método de pago
    pg_query_params($conn, "
        INSERT INTO metodo_pago (nombre_metodo, numero_operacion, id_boleta)
        VALUES ($1, $2, $3)
    ", ['Tarjeta de Crédito', $_POST['tarjeta'], $id_boleta]);

    // Boleta-Servicio
    foreach ($servicios_incluidos_ids as $id_si) {
        pg_query_params($conn, "
            INSERT INTO boleta_servicio (id_boleta, id_servicio_incluido)
            VALUES ($1, $2)
        ", [$id_boleta, $id_si]);
    }

    // Cambiar estado de la reserva
    pg_query_params($conn, "UPDATE reserva SET estado = 'confirmada' WHERE id_reserva = $1", [$id_reserva]);

    pg_query($conn, "COMMIT");
    unset($_SESSION['servicios_temporales']);
    header("Location: reserva_confirmacion.php?id=$id_reserva");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pago de Reserva</title>
    <link rel="stylesheet" href="../../css/Reserva/style_pago_reserva.css">
</head>
<body>
<div class="pago-container">
    <h1>Portal de Pago</h1>
    <p><strong>Reserva de:</strong> <?= htmlspecialchars($reserva['nombre']) ?></p>
    <p><strong>Habitación:</strong> <?= htmlspecialchars($reserva['tipo']) ?></p>
    <p><strong>Estadía:</strong> <?= $noches ?> noches a $<?= number_format($reserva['precio'], 3) ?>/noche</p>
    <p><strong>Total habitación:</strong> $<?= number_format($total_hab, 3) ?></p>

    <p><strong>Servicios incluidos:</strong></p>
    <ul>
        <?php
        if (!empty($_SESSION['servicios_temporales'])) {
            foreach ($_SESSION['servicios_temporales'] as $servicio) {
                $tipo = ucfirst($servicio['tipo_servicio']);
                $desc = $servicio['descripcion'];
                $costo = floatval(str_replace('.', '', $servicio['costo']));
                echo "<li>$tipo - $desc: $" . number_format($costo, 3) . "</li>";
            }
        } else {
            echo "<li>No se han agregado servicios adicionales.</li>";
        }
        ?>
    </ul>
    <p><strong>Total servicios:</strong> $<?= number_format($total_serv, 3) ?></p>
    <p><strong>Total general:</strong> $<?= number_format($total_hab + $total_serv, 3) ?></p>

    <form action="pago_reserva.php" method="POST">
        <input type="hidden" name="id_reserva" value="<?= htmlspecialchars($id_reserva) ?>">
        <label>Número de Tarjeta</label>
        <input type="text" name="tarjeta" maxlength="19" required>
        <label>Nombre del Titular</label>
        <input type="text" name="titular" required>
        <label>Vencimiento</label>
        <input type="month" name="vencimiento" required>
        <label>CVV</label>
        <input type="text" name="cvv" maxlength="4" required>
        <button type="submit">Pagar Ahora</button>
    </form>

    <a href="../index.php" class="btn">Volver a Página Principal</a>
</div>
</body>
</html>
