<?php
include("../conexion.php");
session_start();

// Determinar el rol actual desde sesión o URL (por si se pasa ?rol=huesped)
$rol = $_SESSION['rol'] ?? 'publico';
$username = $_SESSION['username'] ?? '';

// Si es huésped, obtener su información personal desde la base de datos
$huesped_data = null;
if ($rol === 'huesped' && !empty($username)) {
    $consulta = pg_query_params($conn, 
        "SELECT h.nombre, h.email 
        FROM huesped h JOIN usuario u ON h.id_usuario = u.id_usuario
        WHERE u.username = $1", 
        array($username)
    );
    //almacena todos los datos de la consulta
    $huesped_data = pg_fetch_assoc($consulta);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Página Principal</title>
<link rel="stylesheet" href="../../css/style_huesped.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>

    <!-- HEADER -->

<header class="header">
        <!-- MENU HORIZONTAL, PREDETERMINADO -->
    <div class="logo">Y <span>Hotel</span></div>
    <nav>
        <ul class="nav-links">
            <li><a href="panel_huesped.php">Inicio</a></li>
            <li><a href="habitaciones.php">Habitaciones</a></li>
            <li><a href="servicios.html">Servicios</a></li>
            <li><a href="blog.html">Blog</a></li>
            <li><a href="contacto.html">Contactos</a></li>
        </ul>
    </nav>

        <!-- BOTON DATOS DE USUARIOS -->
    <div class="right-nav">

        <?php if ($rol === 'huesped' && $huesped_data): ?>
            <div class="user-dropdown">
                <button class="user-btn"> <span> Bienvenido </span><?= htmlspecialchars($huesped_data['nombre']) ?> </button>
                <div class="dropdown-content">
                    <a href="funcionalidades/misdatos.php">Mis Datos</a>
                    <a href="reservas.php">Reservas Hechas</a>
                    <a href="cambiar_contrasena.php">Cambiar Contraseña</a>
                    <a href="../login/logout.php">Cerrar Sesión</a>
                </div>
            </div>
        <?php elseif ($rol === 'publico'): ?>
            <a href="../php/login/login.php" class="btn-login">Login ➜</a>
        <?php else: ?>
            <a href="../login/logout.php" class="btn-login">Cerrar sesión</a>
        <?php endif; ?>

    </div>

</header>

<section class="slider">
    <!-- Fondo 1 -->
    <div class="slide active" style="background-image: url('../../img/fondo1.jpg')">
        <div class="info">
            <h1>Habitación 1</h1>
            <p>Escapada Simple</p>
            <a href="#" class="btn">Ver Detalle</a>
        </div>
    </div>
    <!-- Fondo 2 -->
    <div class="slide" style="background-image: url('../../img/fondo2.jpg')">
        <div class="info">
            <h1>Habitación 2</h1>
            <p>Escapada de lujo</p>
            <a href="#" class="btn">Ver Detalle</a>
        </div>
    </div>
    <!-- Fondo 3 -->
    <div class="slide" style="background-image: url('../../img/fondo3.jpg')">
        <div class="info">
            <h1>Habitación 3</h1>
            <p>Estancia inolvidable</p>
            <a href="#" class="btn">Ver Detalle</a>
        </div>
    </div>
</section>


<script>
    let slides = document.querySelectorAll('.slide');
    let index = 0;

    setInterval(() => {
        slides[index].classList.remove('active');
        index = (index + 1) % slides.length;
        slides[index].classList.add('active');
    }, 3000);
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const userBtn = document.querySelector(".user-btn");
        const dropdown = document.querySelector(".dropdown-content");

        if (userBtn && dropdown) {
            userBtn.addEventListener("click", function (e) {
                e.stopPropagation();
                dropdown.classList.toggle("show-dropdown");
            });

            document.addEventListener("click", function () {
                dropdown.classList.remove("show-dropdown");
            });
        }
    });
</script>



<footer class="footer">
    <p>&copy; 2025 Y Hotel. Todos los derechos reservados.</p>
</footer>

</body>
</html>
