<?php
session_start();
include("../../php/conexion.php");

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'huesped') {
    header("Location: ../../php/login/login.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'] ?? null;

if (!$id_usuario) {
    echo "<p>Sesión inválida.</p>";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_reserva = $_POST['id_reserva'];
    $tarjeta = $_POST['tarjeta'];
    $titular = $_POST['titular'];
    $vencimiento = $_POST['vencimiento'];
    $cvv = $_POST['cvv'];

    $verificacion = pg_query_params($conn, "
        SELECT r.estado, r.fecha_entrada, r.fecha_salida, r.id_habitacion, h.precio
        FROM reserva r
        JOIN habitacion h ON r.id_habitacion = h.id_habitacion
        WHERE r.id_reserva = $1
    ", [$id_reserva]);

    $reserva = pg_fetch_assoc($verificacion);
    if (!$reserva || $reserva['estado'] !== 'pendiente') {
        echo "<p>Error: esta reserva ya fue pagada o no existe.</p>";
        exit();
    }

    $id_habitacion = $reserva['id_habitacion'];
    $precio_noche = $reserva['precio'];
    $noches = (new DateTime($reserva['fecha_entrada']))->diff(new DateTime($reserva['fecha_salida']))->days;
    $total_base = $precio_noche * $noches;
    $fecha_actual = date('Y-m-d');

    $servicios = pg_query_params($conn, "
        SELECT id_servicio, tipo_servicio, id_servicio_incluido
        FROM servicio_incluido
        WHERE id_habitacion = $1 AND (id_reserva IS NULL OR id_reserva = $2)
    ", [$id_habitacion, $id_reserva]);

    $costo_servicios = 0;
    $servicios_incluidos = [];

    while ($row = pg_fetch_assoc($servicios)) {
        $id_servicio = $row['id_servicio'];
        $tipo = strtolower($row['tipo_servicio']);
        $id_si = $row['id_servicio_incluido'];
        $costo = 0;

        switch ($tipo) {
            case 'habitacion':
                pg_query_params($conn, "UPDATE servicio_habitacion SET fecha_servicio = $1 WHERE id_servicio_habitacion = $2", [$fecha_actual, $id_servicio]);
                $res = pg_query_params($conn, "SELECT costo FROM servicio_habitacion WHERE id_servicio_habitacion = $1", [$id_servicio]);
                break;
            case 'lavanderia':
                pg_query_params($conn, "UPDATE servicio_lavanderia SET fecha_servicio = $1 WHERE id_servicio_lavanderia = $2", [$fecha_actual, $id_servicio]);
                $res = pg_query_params($conn, "SELECT costo FROM servicio_lavanderia WHERE id_servicio_lavanderia = $1", [$id_servicio]);
                break;
            case 'transporte':
                pg_query_params($conn, "UPDATE servicio_transporte SET fecha_servicio = $1 WHERE id_servicio_transporte = $2", [$fecha_actual, $id_servicio]);
                $res = pg_query_params($conn, "SELECT costo FROM servicio_transporte WHERE id_servicio_transporte = $1", [$id_servicio]);
                break;
            default:
                continue 2;
        }

        if ($res && pg_num_rows($res) > 0) {
            $costo = pg_fetch_result($res, 0, 0);
        }

        $costo_servicios += $costo;
        $servicios_incluidos[] = $id_si;

        pg_query_params($conn, "UPDATE servicio_incluido SET id_reserva = $1 WHERE id_servicio_incluido = $2", [$id_reserva, $id_si]);
    }

    $total_final = $total_base + $costo_servicios;
    sleep(2);

    pg_query_params($conn, "UPDATE reserva SET estado = 'confirmada' WHERE id_reserva = $1", [$id_reserva]);
    pg_query_params($conn, "UPDATE habitacion SET estado = 'ocupada' WHERE id_habitacion = $1", [$id_habitacion]);

    $boleta = pg_query_params($conn, "
        INSERT INTO boleta (monto, fecha_pago, estado_pago, id_reserva)
        VALUES ($1, $2, 'pagado', $3)
        RETURNING id_boleta
    ", [$total_final, $fecha_actual, $id_reserva]);

    $id_boleta = ($boleta && pg_num_rows($boleta) > 0) ? pg_fetch_result($boleta, 0, 0) : null;

    if ($id_boleta) {
        pg_query_params($conn, "
            INSERT INTO metodo_pago (nombre_metodo, numero_operacion, id_boleta)
            VALUES ('tarjeta', $1, $2)
        ", [$tarjeta, $id_boleta]);

        foreach ($servicios_incluidos as $id_si) {
            pg_query_params($conn, "
                INSERT INTO boleta_servicio (id_boleta, id_servicio_incluido)
                VALUES ($1, $2)
            ", [$id_boleta, $id_si]);
        }
    }

    header("Location: reserva_confirmacion.php?exito=1");
    exit();
}

$id_reserva = $_GET['id'] ?? null;

if (!$id_reserva) {
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
$total = $reserva['precio'] * $noches;
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
    <p><strong>Total habitación:</strong> $<?= number_format($total, 3) ?></p>

    <?php
    $servicios = pg_query_params($conn, "
        SELECT s.tipo_servicio, s.id_servicio, si.id_servicio_incluido
        FROM servicio_incluido s
        JOIN habitacion h ON s.id_habitacion = h.id_habitacion
        JOIN reserva r ON r.id_reserva = $1 AND r.id_habitacion = h.id_habitacion
        JOIN servicio_incluido si ON si.id_servicio_incluido = s.id_servicio_incluido
        WHERE h.id_habitacion = r.id_habitacion
    ", [$id_reserva]);

    echo "<p><strong>Servicios incluidos:</strong></p><ul>";
    $total_serv = 0;
    while ($row = pg_fetch_assoc($servicios)) {
        $id = $row['id_servicio'];
        $tipo = strtolower($row['tipo_servicio']);
        $costo = 0;
        switch ($tipo) {
            case 'habitacion':
                $res = pg_query_params($conn, "SELECT costo FROM servicio_habitacion WHERE id_servicio_habitacion = $1", [$id]);
                if ($res && pg_num_rows($res) > 0) $costo = pg_fetch_result($res, 0, 0);
                echo "<li>Servicio de habitación: $" . number_format($costo, 3) . "</li>";
                break;
            case 'lavanderia':
                $res = pg_query_params($conn, "SELECT costo FROM servicio_lavanderia WHERE id_servicio_lavanderia = $1", [$id]);
                if ($res && pg_num_rows($res) > 0) $costo = pg_fetch_result($res, 0, 0);
                echo "<li>Servicio de lavandería: $" . number_format($costo, 3) . "</li>";
                break;
            case 'transporte':
                $res = pg_query_params($conn, "SELECT costo FROM servicio_transporte WHERE id_servicio_transporte = $1", [$id]);
                if ($res && pg_num_rows($res) > 0) $costo = pg_fetch_result($res, 0, 0);
                echo "<li>Servicio de transporte: $" . number_format($costo, 3) . "</li>";
                break;
        }
        $total_serv += $costo;
    }
    echo "</ul>";
    echo "<p><strong>Total servicios:</strong> $" . number_format($total_serv, 3) . "</p>";
    echo "<p><strong>Total general:</strong> $" . number_format($total + $total_serv, 3) . "</p>";
    ?>

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
</div>
</body>
</html>