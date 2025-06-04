<?php
session_start();
include("../../php/conexion.php");


// Obtener el id_reserva
$id_reserva = isset($_GET['id_reserva']) ? pg_escape_string($conn, $_GET['id_reserva']) : '';

// Inicializar arreglo de servicios temporales si no existe
if (!isset($_SESSION['servicios_temporales'])) {
    $_SESSION['servicios_temporales'] = [];
}

// Obtener arrays actuales de servicios seleccionados (tipo[] e id_servicio[]), si vienen en GET
$tipos_seleccionados = $_GET['tipo'] ?? [];
$ids_seleccionados = $_GET['id_servicio'] ?? [];

if (!is_array($tipos_seleccionados)) $tipos_seleccionados = [$tipos_seleccionados];
if (!is_array($ids_seleccionados)) $ids_seleccionados = [$ids_seleccionados];

// Procesar adición de servicios enviados por GET
foreach ($tipos_seleccionados as $index => $tipo_nuevo) {
    $id_servicio_nuevo = $ids_seleccionados[$index] ?? null;
    if (!$id_servicio_nuevo) continue;

    // Evitar duplicados en la sesión
    $existe = false;
    foreach ($_SESSION['servicios_temporales'] as $servicio) {
        if ($servicio['tipo_original'] === $tipo_nuevo && $servicio['id_original'] == $id_servicio_nuevo) {
            $existe = true;
            break;
        }
    }
    if ($existe) continue;

    // Consultar detalles del servicio según el tipo
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

// Inicializar arrays para los servicios disponibles
$servicios = [
    'transporte' => [],
    'lavanderia' => [],
    'habitacion' => []
];

// Consulta SQL para obtener los servicios con su detalle
$sql = "
SELECT 
    si.id_servicio_incluido,
    si.tipo_servicio,
    si.id_servicio,
    st.descripcion,
    st.costo,
    si.personal_encargado
FROM servicio_incluido si
JOIN servicio_transporte st ON si.tipo_servicio = 'transporte' AND si.id_servicio = st.id_servicio_transporte

UNION ALL

SELECT 
    si.id_servicio_incluido,
    si.tipo_servicio,
    si.id_servicio,
    sl.descripcion,
    sl.costo,
    si.personal_encargado
FROM servicio_incluido si
JOIN servicio_lavanderia sl ON si.tipo_servicio = 'lavanderia' AND si.id_servicio = sl.id_servicio_lavanderia

UNION ALL

SELECT 
    si.id_servicio_incluido,
    si.tipo_servicio,
    si.id_servicio,
    sh.descripcion,
    sh.costo,
    si.personal_encargado
FROM servicio_incluido si
JOIN servicio_habitacion sh ON si.tipo_servicio = 'habitacion' AND si.id_servicio = sh.id_servicio_habitacion

ORDER BY tipo_servicio, id_servicio_incluido;
";

$result = pg_query($conn, $sql);

if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $tipo = $row['tipo_servicio'];
        $servicios[$tipo][] = $row;
    }
} else {
    echo "Error en la consulta: " . pg_last_error($conn);
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Servicios del Hotel</title>
    <link rel="stylesheet" href="../../css/Servicios/style_servicio.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<!-- ======= HEADER ======= -->
<header class="header">
    <div class="logo"><span>Hotel</span></div>

    <ul class="nav-links">
        <li><a href="../index.php">Inicio</a></li>
        <li><a href="../habitacion/habitaciones.php">Habitaciones</a></li>
        <li><a href="servicios.php?id_reserva=<?= urlencode($id_reserva) ?>">Servicios</a></li>
        <li><a href="../contacto.php">Contacto</a></li>
    </ul>

    <div class="right-nav">
        <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'huesped') : ?>
        <div class="user-dropdown">
            <button class="user-btn" onclick="toggleDropdown()">👤 <?= htmlspecialchars($_SESSION['username']) ?></button>
            <div id="userDropdown" class="dropdown-content">
                <a href="../../php/huesped/datos_huesped.php">Datos</a>
                <a href="../../php/huesped/reservas_hechas.php">Reservas hechas</a>
                <a href="../../cambiar_password.php">Cambio de contraseña</a>
                <a href="../../php/login/logout.php">Cerrar sesión</a>
            </div>
        </div>
        <?php else : ?>
        <?php $url_actual = $_SERVER['REQUEST_URI']; ?>    
        <a href="../../php/login/login.php?redirect=<?= urlencode($url_actual) ?>" class="btn-login">Login ➔</a>
        <?php endif; ?>
    </div>
</header>

<!-- ======= SECCION PRINCIPAL ======= -->
<main class="servicios-container">
    <h1>Servicios Exclusivos para Tu Comodidad</h1>

    <!-- Transporte -->
    <section class="servicio-categoria">
        <h2><i class="fa-solid fa-car"></i> Transporte</h2>
        <div class="servicios-grid">
            <?php foreach ($servicios['transporte'] as $t): ?>
                <a href="detalle_servicio.php?
                    tipo=<?= urlencode($t['tipo_servicio']) ?>&
                    id_servicio=<?= $t['id_servicio'] ?>&
                    id_reserva=<?= urlencode($id_reserva) ?>
                    <?php
                    // Añadir arrays acumulados actuales a la URL
                    foreach ($tipos_seleccionados as $i => $tipo_sel) {
                        echo "&tipo[$i]=" . urlencode($tipo_sel);
                    }
                    foreach ($ids_seleccionados as $i => $id_sel) {
                        echo "&id_servicio[$i]=" . urlencode($id_sel);
                    }
                    ?>
                " class="servicio-card">
                    <h3><?= htmlspecialchars($t['descripcion']) ?></h3>
                    <p><i class="fa-solid fa-dollar-sign"></i> <?= number_format($t['costo'], 3) ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Lavandería -->
    <section class="servicio-categoria">
        <h2><i class="fa-solid fa-shirt"></i> Lavandería</h2>
        <div class="servicios-grid">
            <?php foreach ($servicios['lavanderia'] as $l): ?>
                <a href="detalle_servicio.php?
                    tipo=<?= urlencode($l['tipo_servicio']) ?>&
                    id_servicio=<?= $l['id_servicio'] ?>&
                    id_reserva=<?= urlencode($id_reserva) ?>
                    <?php
                    foreach ($tipos_seleccionados as $i => $tipo_sel) {
                        echo "&tipo[$i]=" . urlencode($tipo_sel);
                    }
                    foreach ($ids_seleccionados as $i => $id_sel) {
                        echo "&id_servicio[$i]=" . urlencode($id_sel);
                    }
                    ?>
                " class="servicio-card">
                    <h3><?= htmlspecialchars($l['descripcion']) ?></h3>
                    <p><i class="fa-solid fa-dollar-sign"></i> <?= number_format($l['costo'], 3) ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Habitación -->
    <section class="servicio-categoria">
        <h2><i class="fa-solid fa-bed"></i> Habitación</h2>
        <div class="servicios-grid">
            <?php foreach ($servicios['habitacion'] as $h): ?>
                <a href="detalle_servicio.php?
                    tipo=<?= urlencode($h['tipo_servicio']) ?>&
                    id_servicio=<?= $h['id_servicio'] ?>&
                    id_reserva=<?= urlencode($id_reserva) ?>
                    <?php
                    foreach ($tipos_seleccionados as $i => $tipo_sel) {
                        echo "&tipo[$i]=" . urlencode($tipo_sel);
                    }
                    foreach ($ids_seleccionados as $i => $id_sel) {
                        echo "&id_servicio[$i]=" . urlencode($id_sel);
                    }
                    ?>
                " class="servicio-card">
                    <h3><?= htmlspecialchars($h['descripcion']) ?></h3>
                    <p><i class="fa-solid fa-dollar-sign"></i> <?= number_format($h['costo'], 3) ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<!-- MOSTRAR/OCULTAR MENU DESPLEGABLE -->
<script>
function toggleDropdown() {
    const dropdown = document.getElementById("userDropdown");
    dropdown.classList.toggle("show-dropdown");
}

window.onclick = function(event) {
    if (!event.target.matches('.user-btn') && !event.target.closest('.user-dropdown')) {
        const dropdown = document.getElementById("userDropdown");
        if (dropdown && dropdown.classList.contains('show-dropdown')) {
            dropdown.classList.remove('show-dropdown');
        }
    }
};
</script>

<!-- ======= PIE DE PÁGINA ======= -->
<footer class="footer">
    <p>&copy; 2025 Hotel. Todos los derechos reservados.</p>
</footer>

</body>
</html>
