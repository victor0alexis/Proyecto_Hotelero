<?php
session_start();
include("../../php/conexion.php");


echo "<pre>";
print_r($_SESSION['servicios_temporales']);
echo "</pre>";

// Verifica si el usuario está autenticado como huésped
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'huesped') {
    header("Location: ../../php/login/login.php");
    exit();
}

// Obtener ID_USUARIO
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

//Obtener ID_RESERVA de la ultima reserva
$reserva = pg_fetch_assoc($consulta);
$id_reserva = $reserva['id_reserva'];


// Calcular total de noches y valor base
$fecha_inicio = new DateTime($reserva['fecha_entrada']);
$fecha_salida = new DateTime($reserva['fecha_salida']);
$noches = $fecha_inicio->diff($fecha_salida)->days;
$total_base = $reserva['precio'] * $noches;


// Obtener servicios explícitos de la reserva (ID_Reserva no es NULL)
$consulta_servicios_reserva = pg_query_params($conn, "
    SELECT 
        si.Tipo_Servicio AS tipo_servicio,
        si.Personal_Encargado AS personal_encargado,
        COALESCE(sl.Descripcion, sh.Descripcion, st.Descripcion) AS descripcion,
        COALESCE(sl.Costo, sh.Costo, st.Costo) AS costo
    FROM Servicio_Incluido si
    LEFT JOIN Servicio_Lavanderia sl ON si.Tipo_Servicio = 'lavanderia' AND si.ID_Servicio = sl.ID_Servicio_Lavanderia
    LEFT JOIN Servicio_Habitacion sh ON si.Tipo_Servicio = 'habitacion' AND si.ID_Servicio = sh.ID_Servicio_Habitacion
    LEFT JOIN Servicio_Transporte st ON si.Tipo_Servicio = 'transporte' AND si.ID_Servicio = st.ID_Servicio_Transporte
    WHERE si.ID_Reserva = $1
", [$reserva['id_reserva']]);



$servicios_agregados = [];
$total_servicios_agregados = 0;

while ($row = pg_fetch_assoc($consulta_servicios_reserva)) {
    $servicios_agregados[] = $row;
    $total_servicios_agregados += floatval($row['costo']);
}

// Inicializa la sesión de servicios temporales si no existe
if (!isset($_SESSION['servicios_temporales'])) {
    $_SESSION['servicios_temporales'] = [];
}

// Añadir nuevos servicios si vienen por GET
$tipos = $_GET['tipo'] ?? [];
$ids = $_GET['id_servicio'] ?? [];

if (!is_array($tipos)) $tipos = [$tipos];
if (!is_array($ids)) $ids = [$ids];

foreach ($tipos as $index => $tipo_nuevo) {
    $id_servicio_nuevo = $ids[$index] ?? null;
    if (!$id_servicio_nuevo) continue;

    // Evitar duplicados en la sesión
    $existe = false;
    foreach ($_SESSION['servicios_temporales'] as $s) {
        if ($s['tipo_original'] === $tipo_nuevo && $s['id_original'] == $id_servicio_nuevo) {
            $existe = true;
            break;
        }
    }
    if ($existe) continue;

    $consulta_nuevo = null;

    if ($tipo_nuevo === 'transporte') {
        $consulta_nuevo = pg_query_params($conn, "
            SELECT 'transporte' AS tipo_servicio, Descripcion, Costo
            FROM Servicio_Transporte
            WHERE ID_Servicio_Transporte = $1
        ", [$id_servicio_nuevo]);
    } elseif ($tipo_nuevo === 'lavanderia') {
        $consulta_nuevo = pg_query_params($conn, "
            SELECT 'lavanderia' AS tipo_servicio, Descripcion, Costo
            FROM Servicio_Lavanderia
            WHERE ID_Servicio_Lavanderia = $1
        ", [$id_servicio_nuevo]);
    } elseif ($tipo_nuevo === 'habitacion') {
        $consulta_nuevo = pg_query_params($conn, "
            SELECT 'habitacion' AS tipo_servicio, Descripcion, Costo
            FROM Servicio_Habitacion
            WHERE ID_Servicio_Habitacion = $1
        ", [$id_servicio_nuevo]);
    }

    if ($consulta_nuevo && pg_num_rows($consulta_nuevo) > 0) {
        $nuevo_servicio = pg_fetch_assoc($consulta_nuevo);
        $nuevo_servicio['personal_encargado'] = 'No asignado';
        $nuevo_servicio['tipo_original'] = $tipo_nuevo;
        $nuevo_servicio['id_original'] = $id_servicio_nuevo;
        $_SESSION['servicios_temporales'][] = $nuevo_servicio;
    }
}

// Eliminar servicios si vienen por parámetro
if (isset($_GET['eliminar_tipo']) && isset($_GET['eliminar_id'])) {
    $tipo_eliminar = $_GET['eliminar_tipo'];
    $id_eliminar = $_GET['eliminar_id'];

    $_SESSION['servicios_temporales'] = array_filter($_SESSION['servicios_temporales'], function ($s) use ($tipo_eliminar, $id_eliminar) {
        return !($s['tipo_original'] === $tipo_eliminar && $s['id_original'] == $id_eliminar);
    });

    // Redirigir para limpiar URL
    $url = strtok($_SERVER["REQUEST_URI"], '?');
    header("Location: $url");
    exit();
}

// Total servicios temporales
$servicios_temporales = $_SESSION['servicios_temporales'] ?? [];
$total_servicios_temporales = array_sum(array_column($servicios_temporales, 'costo'));
$total_servicios = $total_servicios_agregados + $total_servicios_temporales;
$total = $total_base + $total_servicios;

function buildUrlWithoutService($tipoEliminar, $idEliminar) {
    return basename($_SERVER['PHP_SELF']) . "?eliminar_tipo=$tipoEliminar&eliminar_id=$idEliminar";
}

?>



<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Confirmación de Reserva</title>
    <link rel="stylesheet" href="../../css/Reserva/style_reserva_confirmacion.css">
</head>

<!-- ======= SECCION PRINCIPAL======= -->

<body>
<div class="confirmacion-container">
    <h1>Reserva Confirmada</h1>

    <div class="resumen">

        <!-- ======= DETALLE HABITACION  ======= -->
        <div class="seccion detalle-habitacion">
            <img src="../../img/habitaciones/<?= htmlspecialchars($reserva['imagen']) ?>" 
                alt="Imagen de la habitación <?= htmlspecialchars($reserva['tipo']) ?>" 
                class="habitacion-img">
            <h2><?= htmlspecialchars($reserva['tipo']) ?></h2>
            <p><strong>Precio por noche:</strong> $<?= number_format($reserva['precio'], 3) ?></p>
            <p><?= htmlspecialchars($reserva['descripcion']) ?></p>
        </div>

        <!-- ======= DETALLE DE SERVICIOS AGREGADOS, SI LOS HAY ======= -->
    <?php if (!empty($servicios_agregados) || !empty($servicios_temporales)): ?>
    <div class="seccion detalle-huesped">

            <h3>Servicios Añadidos a la Reserva</h3>
        <ul>
            <?php foreach ($servicios_agregados as $serv): ?>
                <li>
                    <strong><?= ucfirst(htmlspecialchars($serv['tipo_servicio'])) ?>:</strong>
                    <?= htmlspecialchars($serv['descripcion']) ?> — Atendido por <?= htmlspecialchars($serv['personal_encargado']) ?> — 
                    <span>Costo: $<?= number_format($serv['costo'], 3) ?></span>
                </li>
            <?php endforeach; ?>

            <?php foreach ($servicios_temporales as $serv): ?>
                <li>
                    <strong><?= ucfirst(htmlspecialchars($serv['tipo_servicio'])) ?> (Temporal):</strong>
                    <?= htmlspecialchars($serv['descripcion']) ?> — 
                    <span>Costo: $<?= number_format($serv['costo'], 3) ?></span>
                    <a href="<?= buildUrlWithoutService($serv['tipo_original'], $serv['id_original']) ?>" style="color: red; margin-left: 10px;">Eliminar</a>
                </li>
            <?php endforeach; ?>
        </ul>
            
        <p><strong>Total Servicios:</strong> $<?= number_format($total_servicios, 3) ?></p>

        <!-- Botón para añadir mas servicio -->
        <div class="botones">
            <a href="../servicios/servicios.php?id_reserva=<?= urlencode($id_reserva) ?>">Añadir servicios</a>
        </div>

    </div>

    <!-- Botón para añadir servicio, si no los hay -->
    <?php elseif ($reserva['estado'] === 'pendiente'): ?>
        <div class="botones">
            <p>No has añadido ningún servicio adicional.</p>
            <a href="../servicios/servicios.php?id_reserva=<?= urlencode($id_reserva) ?>">Ver servicios</a>
        </div>
        <?php endif; ?>

        <!-- ======= DETALLE DEL HUESPED ======= -->
        <div class="seccion detalle-huesped">
            <h3>Datos del Huésped</h3>
            <p><strong>Nombre:</strong> <?= htmlspecialchars($reserva['nombre']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($reserva['email']) ?></p>
        </div>

        <!-- ======= DETALLE RESERVA ======= -->
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

    <!-- ======= BOTONES(RESERVA ESTADO: PENDIENTE ; CONFIRMADA) ======= -->

    <div class="botones">
        <?php if ($reserva['estado'] === 'pendiente'): ?>
            <a href="pago_reserva.php?id=<?= $reserva['id_reserva'] ?>" class="btn">Pagar Ahora</a>
        <?php endif; ?>

        <?php if ($reserva['estado'] === 'confirmada'): ?>
            <a href="../../php/huesped/reservas_hechas.php" class="btn">← Volver a Mis Reservas</a>
        <?php endif; ?>
        <a href="../index.php" class="btn">Volver a Página Principal</a>
    </div>

</div>

</body>
</html>
