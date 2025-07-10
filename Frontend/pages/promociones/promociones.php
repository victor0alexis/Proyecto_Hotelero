<?php
include("../../php/conexion.php");
session_start();

// Obtener promociones vigentes (hoy entre fecha_inicio y fecha_fin)
$query = "SELECT * FROM promocion WHERE CURRENT_DATE BETWEEN fecha_inicio AND fecha_fin ORDER BY fecha_inicio DESC";
$result = pg_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Promociones - Hotel</title>
  <link rel="stylesheet" href="../../css/style_promociones.css" />
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet" />
</head>
<body>

<header class="header">
  <div class="logo">HOTEL</div>
  <ul class="nav-links">
    <li><a href="../index.php">Inicio</a></li>
    <li><a href="../habitacion/habitaciones.php">Habitaciones</a></li>
    <li><a href="../servicios/servicios.php">Servicios</a></li>
    <li><a href="promociones.php" class="active">Promociones</a></li>
  </ul>
  <div class="right-nav">
    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'huesped'): ?>
      <div class="user-dropdown">
        <button class="user-btn" onclick="toggleDropdown()">👤 <?= htmlspecialchars($_SESSION['username']) ?></button>
        <div id="userDropdown" class="dropdown-content">
          <a href="../../php/huesped/reservas_hechas.php">Reservas Hechas</a>
          <a href="../../php/huesped/opiniones/index.php">Mis Opiniones</a>
          <a href="../../php/login/logout.php">Cerrar Sesión</a>
        </div>
      </div>
    <?php else: ?>
      <a href="../../php/login/login.php" class="btn-login">Login ➔</a>
    <?php endif; ?>
  </div>
</header>

<main class="promociones-section">
  <h2 class="promociones-title">Promociones Vigentes</h2>

  <?php if (pg_num_rows($result) === 0): ?>
    <p style="text-align:center; color:#ccc; margin-top: 2rem;">No hay promociones vigentes actualmente.</p>
  <?php else: ?>
    <div class="promociones-grid">
      <?php while ($promo = pg_fetch_assoc($result)): ?>
        <div class="promocion-card">
          <h3><?= htmlspecialchars($promo['titulo']) ?></h3>
          <p class="descripcion"><?= nl2br(htmlspecialchars($promo['descripcion'])) ?></p>
          <p class="fechas">Vigente desde <?= date('d/m/Y', strtotime($promo['fecha_inicio'])) ?> hasta <?= date('d/m/Y', strtotime($promo['fecha_fin'])) ?></p>
          <p class="descuento">Descuento: <?= htmlspecialchars($promo['descuento']) ?>%</p>
        </div>
      <?php endwhile; ?>
    </div>
  <?php endif; ?>
</main>

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

