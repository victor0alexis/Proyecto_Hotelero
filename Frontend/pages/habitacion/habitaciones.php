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
  <link rel="stylesheet" href="../../css/Habitaciones/style_habitaciones.css">
  <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<!-- ======= HEADER ======= -->
<header class="header">

<div class="logo"><span>Hotel</span></div>

    <nav>
  <!-- Parte izquierda Barra Navegacion-->
        <ul class="nav-links">
        <li><a href="../index.php" class="active">Inicio</a></li>
        <li><a href="habitaciones.php" class="active">Habitaciones</a></li>
        <li><a href="servicios.html">Servicios</a></li>
        <li><a href="blog.html">Blog</a></li>
        <li><a href="contacto.html">Contacto</a></li>
        </ul>
    </nav>

  <!-- Parte derecha Barra Navegacion(Datos de usuario ; Login )-->
<div class="right-nav">

    <!-- Si el usuario está logueado como huésped:-->
    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'huesped') : ?>

      <!-- Si el usuario está logueado como huésped: Mostrar Datos-->
      <div class="usuario-info">
          <span>👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
          <a href="../../php/login/logout.php" class="btn-cerrar">Cerrar sesión</a>
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


<!-- ======= LISTADO DE HABITACIONES ======= -->

<section class="habitaciones">
  <?php while ($habitacion = pg_fetch_assoc($consulta)) : ?>
    <?php 
      $imagen = !empty($habitacion['imagen']) ? $habitacion['imagen'] : 'default.jpg';
      $id = $habitacion['id_habitacion'];
    ?>
    <a href="detalle_habitacion.php?id=<?= $id ?>" class="habitacion-link">
      <div class="habitacion-card">
        <img src="../../img/habitaciones/<?= htmlspecialchars($imagen) ?>" alt="Habitación <?= htmlspecialchars($habitacion['tipo']) ?>" class="habitacion-imagen">
        <h2><?= htmlspecialchars($habitacion['tipo']) ?></h2>
        <p><strong>Precio:</strong> $<?= number_format($habitacion['precio'], 2) ?></p>
        <p><strong>Estado:</strong> <?= htmlspecialchars($habitacion['estado']) ?></p>
        <p><strong>Hotel:</strong> <?= htmlspecialchars($habitacion['nombre_hotel']) ?></p>
      </div>
    </a>
  <?php endwhile; ?>
</section>

<!-- ======= PIE DE PÁGINA ======= -->
<footer class="footer">
  <p>&copy; 2025 Hotel H. Todos los derechos reservados.</p>
</footer>

<!-- Script para mostrar/ocultar menú desplegable -->
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

</body>
</html>
