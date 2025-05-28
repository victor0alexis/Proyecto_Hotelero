<?php
session_start();
include("../../php/conexion.php");

// Consulta para obtener los servicios
$servicios = [
    'transporte' => pg_query($conn, "SELECT * FROM servicio_transporte"),
    'lavanderia' => pg_query($conn, "SELECT * FROM servicio_lavanderia"),
    'habitacion' => pg_query($conn, "SELECT * FROM servicio_habitacion"),
];
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

<main class="servicios-container">
    <h1>Servicios Exclusivos para Tu Comodidad</h1>

    <section class="servicio-categoria">
        <h2><i class="fa-solid fa-car"></i> Transporte</h2>
        <div class="servicios-grid">
            <?php while ($t = pg_fetch_assoc($servicios['transporte'])): ?>
                <a href="detalle_servicio.php?tipo=transporte&id=<?= $t['id_servicio_transporte'] ?>" class="servicio-card">
                    <h3><?= htmlspecialchars($t['descripcion']) ?></h3>
                    <p><i class="fa-solid fa-dollar-sign"></i> <?= number_format($t['costo'], 3) ?></p>
                </a>
            <?php endwhile; ?>
        </div>
    </section>

    <section class="servicio-categoria">
        <h2><i class="fa-solid fa-shirt"></i> Lavandería</h2>
        <div class="servicios-grid">
            <?php while ($l = pg_fetch_assoc($servicios['lavanderia'])): ?>
                <a href="detalle_servicio.php?tipo=lavanderia&id=<?= $l['id_servicio_lavanderia'] ?>" class="servicio-card">
                    <h3><?= htmlspecialchars($l['descripcion']) ?></h3>
                    <p><i class="fa-solid fa-dollar-sign"></i> <?= number_format($l['costo'], 2) ?></p>
                </a>
            <?php endwhile; ?>
        </div>
    </section>

    <section class="servicio-categoria">
        <h2><i class="fa-solid fa-bell-concierge"></i> Servicio a la Habitación</h2>
        <div class="servicios-grid">
            <?php while ($h = pg_fetch_assoc($servicios['habitacion'])): ?>
                <a href="detalle_servicio.php?tipo=habitacion&id=<?= $h['id_servicio_habitacion'] ?>" class="servicio-card">
                    <h3><?= htmlspecialchars($h['descripcion']) ?></h3>
                    <p><i class="fa-solid fa-dollar-sign"></i> <?= number_format($h['costo'], 3) ?></p>
                </a>
            <?php endwhile; ?>
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
    <p>&copy; 2025  Hotel. Todos los derechos reservados.</p>
</footer>


</body>
</html>
