<?php
session_start();
include("../../php/conexion.php");


// Verifica si el usuario está autenticado
$id_usuario = $_SESSION['id_usuario'] ?? null;



// Verifica los parámetros de la URL
$id_reserva = $_GET['id_reserva'] ?? null;
$tipo = $_GET['tipo'] ?? null;
$id_servicio = $_GET['id_servicio'] ?? null;



// Definir la tabla según el tipo
switch ($tipo) {
    case 'transporte':
        $tabla = 'servicio_transporte';
        $columna_id = 'id_servicio_transporte';
        break;
    case 'lavanderia':
        $tabla = 'servicio_lavanderia';
        $columna_id = 'id_servicio_lavanderia';
        break;
    case 'habitacion':
        $tabla = 'servicio_habitacion';
        $columna_id = 'id_servicio_habitacion';
        break;
    default:
        header("Location: servicios.php?id_reserva=" . urlencode($id_reserva));
        exit();
}

// Obtener el servicio específico
$query = pg_query_params($conn, "SELECT * FROM $tabla WHERE $columna_id = $1", [$id_servicio]);

if (!$query || pg_num_rows($query) === 0) {
    echo "No se encontró el servicio seleccionado.";
    exit();
}

$servicio = pg_fetch_assoc($query);

// Obtener servicios temporales actuales (si vienen en GET)
$servicios_tipo = $_GET['tipo'] ?? [];
$servicios_id = $_GET['id_servicio'] ?? [];



if (!is_array($servicios_tipo)) $servicios_tipo = [$servicios_tipo];
if (!is_array($servicios_id)) $servicios_id = [$servicios_id];

// Evitar agregar repetidos: Solo agregar el servicio actual (pasado por GET tipo e id_servicio) si no está en el array
$existe = false;
for ($i = 0; $i < count($servicios_tipo); $i++) {
    if ($servicios_tipo[$i] === $tipo && $servicios_id[$i] === $id_servicio) {
        $existe = true;
        break;
    }
}
if (!$existe) {
    $servicios_tipo[] = $tipo;
    $servicios_id[] = $id_servicio;
}


// Construir parámetros para URL con arrays tipo[] e id_servicio[]
$params = [
    'id_reserva' => $id_reserva,
];

// Agregar arrays a URL
foreach ($servicios_tipo as $i => $t) {
    $params["tipo[$i]"] = $t;
}
foreach ($servicios_id as $i => $idS) {
    $params["id_servicio[$i]"] = $idS;
}

$url_anadir = '../reservas/reserva_confirmacion.php?' . http_build_query($params);

?>




<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del Servicio</title>
    <link rel="stylesheet" href="../../css/Servicios/style_detalle_servicios.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>

<!-- ======= HEADER ======= -->
<header class="header">
<div class="logo"><span>Hotel</span></div>

<ul class="nav-links">
    <li><a href="../index.php">Inicio</a></li>
    <li><a href="../habitacion/habitaciones.php">Habitaciones</a></li>
    <li><a href="servicios.php">Servicios</a></li>
    <li><a href="../contacto.php">Contacto</a></li>
</ul>

<div class="right-nav">
    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'huesped') : ?>
    <div class="user-dropdown">
        <button class="user-btn" onclick="toggleDropdown()">👤 <?= htmlspecialchars($_SESSION['username']) ?></button>
        <div id="userDropdown" class="dropdown-content">
            <a href="../../php/huesped/reservas_hechas.php">Reservas hechas</a>
            <a href="../../php/login/logout.php">Cerrar sesión</a>
        </div>
    </div>
    <?php else : ?>
    <?php $url_actual = $_SERVER['REQUEST_URI']; ?>    
    <a href="../../php/login/login.php?redirect=<?= urlencode($url_actual) ?>" class="btn-login">Login ➔</a>
    <?php endif; ?>
</div>
</header>


    <!-- ======= SECCION PRINCIPAL======= -->


<!-- Detalle servicio -->
<div class="detalle-container">
    <h1><i class="fa-solid fa-circle-info"></i> Detalle del Servicio</h1>
    <p><i class="fa-solid fa-tags"></i> <strong>Tipo:</strong> <?= ucfirst(htmlspecialchars($tipo)) ?></p>
    <p><i class="fa-solid fa-file-alt"></i> <strong>Descripción:</strong> <?= htmlspecialchars($servicio['descripcion']) ?></p>
    <p><i class="fa-solid fa-dollar-sign"></i> <strong>Costo:</strong> <?= number_format($servicio['costo'], 3) ?></p>
    
<!-- Botón para añadir servicio (solo si hay id_reserva) -->
<?php if (!empty($id_reserva)): ?>
    <a href="<?= $url_anadir ?>" class="btn-volver">
        <i class="fa-solid fa-plus"></i> Añadir a la Reserva
    </a>
<?php endif; ?>

    <!-- Botón para volver -->
    <a href="servicios.php?id_reserva=<?= urlencode($id_reserva) ?>" class="btn-volver">
        <i class="fa-solid fa-arrow-left"></i> Volver
    </a>
</div>


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
    <p>&copy; 2025  Hotel. Todos los derechos reservados.</p>
</footer>

</body>
</html>
