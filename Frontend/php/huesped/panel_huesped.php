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
<title>Hotel H | Taller Icinf</title>
<link rel="stylesheet" href="../../css/style_principal.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>

    <!-- HEADER -->

<header class="header">
        <!-- MENU HORIZONTAL, PREDETERMINADO -->
    <div class="logo"> <span>HOTEL H</span></div>
    <nav>
      <ul class="nav-links">
        <li><a href="index.html">INICIO</a></li>
        <li><a href="habitaciones.php">HABITACIONES</a></li>
        <li><a href="servicios.html">SERVICIOS</a></li>
        <li><a href="blog.html">BLOG</a></li>
        <li><a href="contacto.html">CONTACTO</a></li>
      </ul>
    </nav>

        <!-- BOTON DATOS DE USUARIOS -->
        <div class="right-nav">
    <?php if ($rol === 'huesped' && $huesped_data): ?>
        <div class="user-dropdown">
            <button class="user-btn">
                <span class="user-avatar">👤</span>
                <?= htmlspecialchars($huesped_data['nombre']) ?>
            </button>
            <div class="dropdown-content">
                <a href="funcionalidades/misdatos.php">Mis Datos</a>
                <a href="reservas.php">Reservas Hechas</a>
                <a href="cambiar_contrasena.php">Cambiar Contraseña</a>
                <a href="../login/logout.php">Cerrar Sesión</a>
            </div>
        </div>
    <?php elseif ($rol === 'publico'): ?>
        <a href="../php/login/login.php" class="btn-login">LOGIN ➜</a>
    <?php else: ?>
        <a href="../login/logout.php" class="btn-login">Cerrar sesión</a>
    <?php endif; ?>
</div>
</header>

<section>
    <div class="slider">
      <div class="slide active">
        <div class="video-background">
          <video autoplay muted loop playsinline style="width: 100%; height: 100%; object-fit: cover;">
              <source src="../../img/fondo1.mp4" type="video/mp4">
              <source src="../../img/fondo1.webm" type="video/webm">
              Tu navegador no soporta videos HTML5.
          </video>
        </div>
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
document.addEventListener('DOMContentLoaded', function() {
    const userBtn = document.querySelector('.user-btn');
    const dropdown = document.querySelector('.dropdown-content');
    
    if(userBtn && dropdown) {
        // Mostrar/ocultar al hacer clic
        userBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.classList.toggle('show-dropdown');
        });
        
        // Ocultar al hacer clic fuera
        document.addEventListener('click', function() {
            dropdown.classList.remove('show-dropdown');
        });
        
        // Ocultar al presionar Escape
        document.addEventListener('keydown', function(e) {
            if(e.key === 'Escape') {
                dropdown.classList.remove('show-dropdown');
            }
        });
    }
});
</script>

<footer class="footer">
    <p>&copy; Copyright © 2025 Hotel H derechos reservados.</p>
  </footer>

</body>
</html>
