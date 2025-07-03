<?php
include("../../php/conexion.php");
session_start();

// Verificar si el usuario tiene rol de huésped
$rol = $_SESSION['rol'] ?? 'publico';
$huesped_data = null;

if ($rol === 'huesped') {
    $id_usuario = $_SESSION['id_usuario'] ?? null;
    if ($id_usuario) {
        $consulta = pg_query_params($conn, "SELECT nombre FROM huesped WHERE id_usuario = $1", [$id_usuario]);
        if ($consulta && pg_num_rows($consulta) > 0) {
            $huesped_data = pg_fetch_assoc($consulta);
        }
    }
}

// Consulta para obtener habitaciones con datos del hotel
$consulta = pg_query($conn, "
    SELECT 
        h.ID_Habitacion AS id_habitacion, 
        h.Precio AS precio, 
        h.Estado AS estado, 
        h.Tipo AS tipo, 
        ht.Nombre AS nombre_hotel,
        h.Imagen AS imagen
    FROM Habitacion h 
    JOIN Hotel ht ON h.ID_Hotel = ht.ID_Hotel
    ORDER BY h.ID_Habitacion
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Habitaciones</title>
  <link rel="stylesheet" href="../../css/Habitacion/style_habitaciones.css">
  <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>

<!-- ======= HEADER ======= -->
<header class="header">

<div class="logo"><span>HOTEL</span></div>

    <nav>
  <!-- Parte izquierda Barra Navegacion-->
        <ul class="nav-links">
        <li><a href="../index.php" class="active">Inicio</a></li>
        <li><a href="habitaciones.php" class="active">Habitaciones</a></li>
        <li><a href="../servicios/servicios.php">Servicios</a></li>
        <li><a href="../contacto.html">Contacto</a></li>
        </ul>
    </nav>

  <!-- Parte derecha Barra Navegacion(Datos de usuario ; Login )-->
<div class="right-nav">

    <!-- Si el usuario está logueado como huésped:-->
    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'huesped') : ?>

      <!-- Si el usuario está logueado como huésped: Mostrar Datos-->
      <div class="user-dropdown">
        <!-- se crea boton con datos con los datos de usuario.-->
        <button class="user-btn" onclick="toggleDropdown()">👤 <?= htmlspecialchars($_SESSION['username']) ?></button>
        <!-- opciones disponibles para el huesped-->
        <div id="userDropdown" class="dropdown-content">
          <a href="../../php/huesped/reservas_hechas.php">Reservas hechas</a>
          <a href="../../php/login/logout.php">Cerrar sesión</a>
        </div>
      </div>

    <!-- Si el usuario no ha iniciado sesion.-->
    <?php else : ?>
    <!-- se guarda "url_actual"-->
    <?php $url_actual = $_SERVER['REQUEST_URI']; ?>
    <!-- se redirige a Login.php, con la URL codificada como parametro redirect"-->
    <a href="../../php/login/login.php?redirect=<?= urlencode($url_actual) ?>" class="btn-login">Login ➔</a>
    <?php endif; ?>

    </div>

</header>





<!-- ======= SECCIÓN HABITACIONES ======= -->
<section class="habitaciones-section">
  <div class="habitaciones-titulos">
    <h2 class="habitaciones-title">HABITACIONES Y SUITES</h2>
    <p class="habitaciones-subtitle">Descubra nuestras exclusivas habitaciones diseñadas para ofrecer confort, elegancia y una experiencia inolvidable.</p>
  </div>

<div class="habitaciones-grid">
  <?php while ($habitacion = pg_fetch_assoc($consulta)) : ?>
    <a href="detalle_habitacion.php?id=<?= $habitacion['id_habitacion'] ?>" class="habitacion-link">
      <div class="habitacion-card">
        <img src="../../img/habitaciones/<?= htmlspecialchars($habitacion['imagen']) ?>" alt="Habitación <?= htmlspecialchars($habitacion['tipo']) ?>" class="habitacion-imagen">
        <div class="habitacion-info">
          <h3><?= strtoupper(htmlspecialchars($habitacion['tipo'])) ?></h3>
          <p><strong>Precio:</strong> $<?= number_format($habitacion['precio']) ?></p>
          <p><strong>Estado:</strong> <?= htmlspecialchars($habitacion['estado']) ?></p>
          <p><strong>Hotel:</strong> <?= htmlspecialchars($habitacion['nombre_hotel']) ?></p>
        </div>
      </div>
    </a>
  <?php endwhile; ?>
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
  <div class="footer-container">
    <div>
      <h3>Contacto</h3>
      <p>Dirección: Av. Principal 123, Ciudad</p>
      <p>Teléfono: (01) 234-5678</p>
      <p>Email: contacto@hotel.com</p>
    </div>
    <div>
      <h3>Sobre Nosotros</h3>
      <p>Hotel  ofrece una experiencia única combinando lujo, confort y elegancia en cada rincón.</p>
    </div>
    <div>
      <h3>Redes Sociales</h3>
      <p><a href="#">Facebook</a></p>
      <p><a href="#">Instagram</a></p>
      <p><a href="#">Twitter</a></p>
    </div>
  </div>
  <div class="footer-bottom">
    © 2025 Hotel. Todos los derechos reservados.
  </div>
</footer>

</body>
</html>
