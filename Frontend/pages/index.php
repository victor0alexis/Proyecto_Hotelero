<?php
include("../php/conexion.php");
session_start();
$sql = "SELECT * FROM Habitacion WHERE Estado = 'Disponible'";
$result = pg_query($conn, $sql);

// Obtener últimas opiniones
$opiniones_query = pg_query($conn, "
    SELECT  o.comentario,
            o.clasificacion,
            o.fecha         AS fecha_opinion,
            h.nombre        AS huesped,
            hab.tipo        AS habitacion,
            r.fecha_entrada,
            r.fecha_salida
    FROM opinion o
    JOIN huesped     h   ON o.id_huesped  = h.id_huesped
    JOIN reserva     r   ON o.id_reserva  = r.id_reserva
    JOIN habitacion  hab ON r.id_habitacion = hab.id_habitacion
    ORDER BY o.fecha DESC
    LIMIT 6
");
?>
  
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Hotel</title>
  <link rel="stylesheet" href="../css/style_principal.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
</head>
<body>

<!-- ======= HEADER ======= -->
<header class="header">
  <div class="logo"><span>HOTEL</span></div>

  <!-- Parte izquierda Barra Navegacion-->
  <ul class="nav-links">
    <li><a href="index.php">Inicio</a></li>
    <li><a href="habitacion/habitaciones.php">Habitaciones</a></li>
    <li><a href="servicios/servicios.php">Servicios</a></li>
    <li><a href="contacto.php">Contacto</a></li>
    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'huesped'): ?>
    <a href="../php/huesped/CRUD/opiniones/index.php" class="btn btn-opinar">
        Opinar sobre el Hotel
    </a>
<?php else: ?>
    <p>Para opinar, debes <a href="/Proyecto_Hotelero/Frontend/php/login/login.php">iniciar sesión</a>.</p>
<?php endif; ?>

  </ul>
  <div class="right-nav">
    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'huesped') : ?>
      <div class="user-dropdown">
        <button class="user-btn" onclick="toggleDropdown()">👤 <?= htmlspecialchars($_SESSION['username']) ?></button>
        <div id="userDropdown" class="dropdown-content">
          <a href="../php/huesped/reservas_hechas.php">Reservas Hechas</a>
          <a href="../php/login/logout.php">Cerrar Sesión</a>
          
        </div>
      </div>
    <?php else : ?>
      <?php $url_actual = $_SERVER['REQUEST_URI']; ?>
      <a href="../php/login/login.php?redirect=<?= urlencode($url_actual) ?>" class="btn-login">Login ➔</a>
    <?php endif; ?>
  </div>

</header>

<!-- ======= SECCION PRINCIPAL ======= -->
<section>
  <div class="slider">
    <div class="slide active">
      <div class="video-background">
        <video autoplay muted loop playsinline style="width: 100%; height: 100%; object-fit: cover;">
          <source src="../img/fondo1.mp4" type="video/mp4">
          <source src="../img/fondo1.mp4" type="video/webm">
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

<!-- ======= OPINIONES DE HUÉSPEDES ======= -->
 
<section class="opiniones-section">
  <h2>Reseñas</h2>

  <div class="opiniones-grid">
    <?php while ($op = pg_fetch_assoc($opiniones_query)): ?>
      <div class="opinion-card">
        <h3><?= htmlspecialchars($op['huesped']) ?></h3>
        <p class="habitacion">
          <strong>Habitación:</strong> <?= htmlspecialchars($op['habitacion']) ?><br>
        <p class="comentario">
          “<?= nl2br(htmlspecialchars($op['comentario'])) ?>”
        </p>
        <p class="clasificacion">
          Calificación: <?= str_repeat('★', $op['clasificacion']) ?><?= str_repeat('☆', 5 - $op['clasificacion']) ?>
        </p>
        <small class="fecha"><?= date('d/m/Y', strtotime($op['fecha_opinion'])) ?></small>
      </div>
    <?php endwhile; ?>
  </div>
</section>

<!-- ======= INFORMACIÓN DE CONTACTO ======= -->
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
