<?php
session_start();
include("../../php/conexion.php");

// Validar y obtener parámetros
$tipo = $_GET['tipo'] ?? '';
$id = $_GET['id'] ?? '';

if (empty($tipo) || empty($id)) {
    header("Location: servicios.php");
    exit();
}

// Sanitizar valores
$tipo = strtolower($tipo);
$id = intval($id);

// Definir la consulta según el tipo
switch ($tipo) {
    case 'transporte':
        $tabla = 'servicio_transporte';
        $campo_id = 'id_servicio_transporte';
        break;
    case 'lavanderia':
        $tabla = 'servicio_lavanderia';
        $campo_id = 'id_servicio_lavanderia';
        break;
    case 'habitacion':
        $tabla = 'servicio_habitacion';
        $campo_id = 'id_servicio_habitacion';
        break;
    default:
        header("Location: servicios.php");
        exit();
}

// Ejecutar la consulta
$result = pg_query_params($conn, "SELECT * FROM $tabla WHERE $campo_id = $1", [$id]);

if (!$result || pg_num_rows($result) === 0) {
    header("Location: servicios.php");
    exit();
}

$servicio = pg_fetch_assoc($result);
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

<div class="detalle-container">
    <h1><i class="fa-solid fa-circle-info"></i> Detalle del Servicio</h1>
    <p><i class="fa-solid fa-tags"></i> <strong>Tipo:</strong> <?= ucfirst(htmlspecialchars($tipo)) ?></p>
    <p><i class="fa-solid fa-file-alt"></i> <strong>Descripción:</strong> <?= htmlspecialchars($servicio['descripcion']) ?></p>
    <p><i class="fa-solid fa-dollar-sign"></i> <strong>Costo:</strong> <?= number_format($servicio['costo'], 3) ?></p>
        
    <a href="servicios.php" class="btn-volver"><i class="fa-solid fa-arrow-left"></i> Volver</a>
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
