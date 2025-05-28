<?php
include("../php/conexion.php"); // Conexión a la base de datos
session_start();               //iniciamos sesion.
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Hotel</title>
  <link rel="stylesheet" href="../css/style_principal.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>


<!-- ======= HEADER ======= -->
<header class="header">
  <div class="logo"><span>Hotel</span></div>

  <!-- Parte izquierda Barra Navegacion-->
  <ul class="nav-links">
    <li><a href="index.php">Inicio</a></li>
    <li><a href="habitacion/habitaciones.php">Habitaciones</a></li>
    <li><a href="servicios/servicios.php">Servicios</a></li>
    <li><a href="contacto.php">Contacto</a></li>
  </ul>


  <!-- Parte derecha Barra Navegacion(Datos de usuario ; Login )-->
  <div class="right-nav">

    <!-- Si el usuario está logueado como huésped:-->
    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'huesped') : ?>
      <div class="user-dropdown">
        <!-- se crea boton con datos con los datos de usuario.-->
        <button class="user-btn" onclick="toggleDropdown()">👤 <?= htmlspecialchars($_SESSION['username']) ?></button>
        <!-- opciones disponibles para el huesped-->
        <div id="userDropdown" class="dropdown-content">
          <a href="../php/huesped/datos_huesped.php">Datos</a>
          <a href="../php/huesped/reservas_hechas.php">Reservas hechas</a>
          <a href="../php/cambiar_password.php">Cambio de contraseña</a>
          <a href="../php/login/logout.php">Cerrar sesión</a>
        </div>
      </div>
    <!-- Si el usuario no ha iniciado sesion.-->
    <?php else : ?>
      <!-- se guarda "url_actual"-->
      <?php $url_actual = $_SERVER['REQUEST_URI']; ?>    
      <!-- se redirige a Login.php, con la URL codificada como parametro redirect"-->
      <a href="../php/login/login.php?redirect=<?= urlencode($url_actual) ?>" class="btn-login">Login ➔</a>
    <?php endif; ?>
  </div>

</header>


<!-- ======= SECCION PRINCIPAL ======= -->

  <section>
    <!-- Fondo 1 -->
  <div class="slider">
    <div class="slide active">
     <div class="video-background">
          <video autoplay muted loop playsinline style="width: 100%; height: 100%; object-fit: cover;">
              <source src="../img/fondo1.mp4" type="video/mp4">
              <!-- Opcional: Añadir formato WebM para mejor compatibilidad -->
              <source src="../img/fondo1.mp4" type="video/webm">
              <!-- Mensaje alternativo si el navegador no soporta video -->
              Tu navegador no soporta videos HTML5.
          </video>
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
    }, 3000); // cada 5 segundos
  </script>

<script>
function toggleDropdown() {
  const dropdown = document.getElementById("userDropdown");
  dropdown.classList.toggle("show-dropdown");
}

window.onclick = function(event) {
  if (!event.target.matches('.user-btn')) {
    const dropdowns = document.getElementsByClassName("dropdown-content");
    for (const openDropdown of dropdowns) {
      if (openDropdown.classList.contains('show-dropdown')) {
        openDropdown.classList.remove('show-dropdown');
      }
    }
  }
}
</script>

</body>
</html>
