<?php
session_start();
include("../../php/conexion.php");


// ============================
// Autenticación y validación
// ============================
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'huesped') {
    header("Location: ../../php/login/login.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario']    ?? null;
$id_reserva = $_GET['id'] ?? $_GET['id_reserva'] ?? null;



// ============================
// Inicializar sesión para servicios temporales
// ============================
if (!isset($_SESSION['servicios_temporales'])) {
    $_SESSION['servicios_temporales'] = [];
}
if (!isset($_SESSION['servicios_temporales'][$id_reserva])) {
    $_SESSION['servicios_temporales'][$id_reserva] = [];
}

// ============================
// Procesar servicios nuevos (GET)
// ============================
if (isset($_GET['tipo']) && isset($_GET['id_servicio'])) {
    $tipos = $_GET['tipo'];
    $ids = $_GET['id_servicio'];

    if (!is_array($tipos)) $tipos = [$tipos];
    if (!is_array($ids)) $ids = [$ids];

    foreach ($tipos as $i => $tipo) {
        $id_serv = $ids[$i] ?? null;
        if (!$id_serv) continue;

        // Evitar duplicados
        $ya_existe = false;
        foreach ($_SESSION['servicios_temporales'][$id_reserva] as $serv) {
            if ($serv['tipo_original'] === $tipo && $serv['id_original'] == $id_serv) {
                $ya_existe = true;
                break;
            }
        }
        if ($ya_existe) continue;

        // Obtener detalles del servicio
        $consulta_nuevo = null;
        if ($tipo === 'transporte') {
            $consulta_nuevo = pg_query_params($conn, "
                SELECT 'transporte' AS tipo_servicio, si.Personal_Encargado,
                       st.Descripcion, st.Costo
                FROM Servicio_Incluido si
                JOIN Servicio_Transporte st ON si.ID_Servicio = st.ID_Servicio_Transporte
                WHERE si.ID_Servicio = $1 AND si.Tipo_Servicio = 'transporte'
            ", [$id_serv]);
        } elseif ($tipo === 'lavanderia') {
            $consulta_nuevo = pg_query_params($conn, "
                SELECT 'lavanderia' AS tipo_servicio, si.Personal_Encargado,
                       sl.Descripcion, sl.Costo
                FROM Servicio_Incluido si
                JOIN Servicio_Lavanderia sl ON si.ID_Servicio = sl.ID_Servicio_Lavanderia
                WHERE si.ID_Servicio = $1 AND si.Tipo_Servicio = 'lavanderia'
            ", [$id_serv]);
        } elseif ($tipo === 'habitacion') {
            $consulta_nuevo = pg_query_params($conn, "
                SELECT 'habitacion' AS tipo_servicio, si.Personal_Encargado,
                       sh.Descripcion, sh.Costo
                FROM Servicio_Incluido si
                JOIN Servicio_Habitacion sh ON si.ID_Servicio = sh.ID_Servicio_Habitacion
                WHERE si.ID_Servicio = $1 AND si.Tipo_Servicio = 'habitacion'
            ", [$id_serv]);
        }

        if ($consulta_nuevo && pg_num_rows($consulta_nuevo) > 0) {
            $datos = pg_fetch_assoc($consulta_nuevo);

            $_SESSION['servicios_temporales'][$id_reserva][] = [
                'tipo_servicio'      => $datos['tipo_servicio'] ?? $tipo,
                'descripcion'        => $datos['descripcion'] ?? 'Sin descripción',
                'costo'              => floatval($datos['costo'] ?? 0),
                'personal_encargado' => $datos['personal_encargado'] ?? null,
                'tipo_original'      => $tipo,
                'id_original'        => $id_serv
            ];
        }
    }
}

// ============================
// Eliminar servicio temporal (GET)
// ============================
if (isset($_GET['eliminar_tipo']) && isset($_GET['eliminar_id'])) {
    $tipo_eliminar = $_GET['eliminar_tipo'];
    $id_eliminar = $_GET['eliminar_id'];

    $_SESSION['servicios_temporales'][$id_reserva] = array_filter(
        $_SESSION['servicios_temporales'][$id_reserva],
        function ($s) use ($tipo_eliminar, $id_eliminar) {
            return !($s['tipo_original'] === $tipo_eliminar && $s['id_original'] == $id_eliminar);
        }
    );

    $url = strtok($_SERVER["REQUEST_URI"], '?');
    header("Location: $url");
    exit();
}

// ============================
// Obtener información de la reserva
// ============================
$consulta = pg_query_params($conn, "
    SELECT r.*, h.tipo, h.precio, h.descripcion, h.imagen, hu.nombre, hu.email
    FROM reserva r
    JOIN huesped hu ON r.id_huesped = hu.id_huesped
    JOIN habitacion h ON r.id_habitacion = h.id_habitacion
    WHERE r.id_reserva = $1 AND hu.id_usuario = $2
    LIMIT 1
", [$id_reserva, $id_usuario]);

if (!$consulta || pg_num_rows($consulta) === 0) {
    echo "<p>No se encontró ninguna reserva reciente.</p>";
    exit();
}

$reserva = pg_fetch_assoc($consulta);

// ============================
// Cálculo de noches y totales
// ============================
$fecha_inicio = new DateTime($reserva['fecha_entrada']);
$fecha_salida = new DateTime($reserva['fecha_salida']);
$noches = $fecha_inicio->diff($fecha_salida)->days;
$total_base = $reserva['precio'] * $noches;

// ============================
// Servicios agregados de la base de datos
// ============================
$servicios_agregados = [];
$total_servicios_agregados = 0;

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
    WHERE si.ID_Reserva = $1
", [$id_reserva]);

while ($row = pg_fetch_assoc($consulta_servicios)) {
    $servicios_agregados[] = $row;
    $total_servicios_agregados += floatval($row['costo']);
}

// ============================
// Totales
// ============================
$servicios_temporales = $_SESSION['servicios_temporales'][$id_reserva] ?? [];
$total_servicios_temporales = array_sum(array_column($servicios_temporales, 'costo'));
$total_servicios = $total_servicios_agregados + $total_servicios_temporales;
$total = $total_base + $total_servicios;

function buildUrlWithoutService($tipoEliminar, $idEliminar) {
    return basename($_SERVER['PHP_SELF']) . "?eliminar_tipo=$tipoEliminar&eliminar_id=$idEliminar";
}
?>

<!-- ============================ HTML ============================ -->
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

        <!-- HABITACIÓN -->
        <div class="seccion detalle-habitacion">
            <img src="../../img/habitaciones/<?= htmlspecialchars($reserva['imagen']) ?>" 
                 alt="Imagen de la habitación <?= htmlspecialchars($reserva['tipo']) ?>" 
                 class="habitacion-img">
            <h2><?= htmlspecialchars($reserva['tipo']) ?></h2>
            <p><strong>Precio por noche:</strong> $<?= number_format($reserva['precio']) ?></p>
            <p><?= htmlspecialchars($reserva['descripcion']) ?></p>
        </div>

        <!-- SERVICIOS -->
<?php if (!empty($servicios_agregados) || !empty($servicios_temporales)): ?>
    <div class="seccion detalle-huesped">
        <h3>Servicios Añadidos a la Reserva</h3>
        <ul>
            <?php foreach ($servicios_agregados as $serv): ?>
                <li><strong><?= ucfirst(htmlspecialchars($serv['tipo_servicio'])) ?>:</strong>
                    <?= htmlspecialchars($serv['descripcion']) ?> —
                    <span>Costo: $<?= number_format($serv['costo']) ?></span>
                </li>
            <?php endforeach; ?>

            <?php foreach ($servicios_temporales as $serv): ?>
                <li><strong><?= ucfirst(htmlspecialchars($serv['tipo_servicio'])) ?> (Temporal):</strong>
                    <?= htmlspecialchars($serv['descripcion']) ?> —
                    <span>Costo: $<?= number_format($serv['costo']) ?></span>
                    <?php if (isset($serv['tipo_original'], $serv['id_original'])): ?>
                        <a href="<?= buildUrlWithoutService($serv['tipo_original'], $serv['id_original']) ?>" style="color: red; margin-left: 10px;">Eliminar</a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <p><strong>Total Servicios:</strong> $<?= number_format($total_servicios) ?></p>

        <?php if ($reserva['estado'] === 'pendiente'): ?>
            <div class="botones">
                <a href="../servicios/servicios.php?id_reserva=<?= urlencode($id_reserva) ?>">Añadir servicios</a>
            </div>
        <?php endif; ?>
    </div>

<?php elseif ($reserva['estado'] === 'pendiente'): ?>
    <div class="botones">
        <p>No has añadido ningún servicio adicional.</p>
        <a href="../servicios/servicios.php?id_reserva=<?= urlencode($id_reserva) ?>">Ver servicios</a>
    </div>
<?php endif; ?>


        <!-- HUÉSPED -->
        <div class="seccion detalle-huesped">
            <h3>Datos del Huésped</h3>
            <p><strong>Nombre:</strong> <?= htmlspecialchars($reserva['nombre']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($reserva['email']) ?></p>
        </div>

        <!-- DETALLE RESERVA -->
        <div class="seccion detalle-reserva">
            <h3>Detalles de la Reserva</h3>
            <p><strong>Fecha de Entrada:</strong> <?= htmlspecialchars($reserva['fecha_entrada']) ?></p>
            <p><strong>Fecha de Salida:</strong> <?= htmlspecialchars($reserva['fecha_salida']) ?></p>
            <p><strong>Noches:</strong> <?= $noches ?></p>
            <p><strong>Total por Noches:</strong> $<?= number_format($total_base) ?></p>
            <p><strong>Total Servicios:</strong> $<?= number_format($total_servicios) ?></p>
            <p><strong>Total a Pagar:</strong> $<?= number_format($total) ?></p>
            <p><strong>Estado:</strong> <?= ucfirst(htmlspecialchars($reserva['estado'])) ?></p>
        </div>
    </div>

    <!-- BOTONES -->
    <div class="botones">
        <?php if ($reserva['estado'] === 'pendiente'): ?>
            <a href="pago_reserva.php?id=<?= $id_reserva ?>" class="btn">Pagar Ahora</a>
        <?php endif; ?>
        <?php if ($reserva['estado'] === 'confirmada'): ?>
            <a href="../../php/huesped/reservas_hechas.php" class="btn">← Volver a Mis Reservas</a>
        <?php endif; ?>
        <a href="../index.php" class="btn">Volver a Página Principal</a>
    </div>
</div>
</body>
</html>
