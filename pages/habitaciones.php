<?php
include("../php/conexion.php");

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
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Habitaciones</title>
  <link rel="stylesheet" href="../css/style_habitaciones.css">
  <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>

<header class="header">
  <div class="logo">Y <span>Hotel</span></div>
  <nav>
    <ul class="nav-links">
      <li><a href="index.html">Inicio</a></li>
      <li><a href="habitaciones.php">Habitaciones</a></li>
      <li><a href="servicios.html">Servicios</a></li>
      <li><a href="blog.html">Blog</a></li>
      <li><a href="contacto.html">Contactos</a></li>
    </ul>
  </nav>
  <div class="right-nav">
    <a href="../php/login/login.php" class="btn-login">Login ➔</a>
  </div>
</header>

<main class="contenido">
  <h1 class="titulo-seccion">Habitaciones Disponibles</h1>
  <section class="habitaciones">
    <?php while ($habitacion = pg_fetch_assoc($consulta)) {
      $imagen = !empty($habitacion['imagen']) ? $habitacion['imagen'] : 'default.jpg';
    ?>
    <div class="habitacion-card">
      <img src="../img/habitaciones/<?= htmlspecialchars($imagen) ?>" alt="Habitación <?= htmlspecialchars($habitacion['tipo']) ?>" class="habitacion-imagen">
      <h2><?= htmlspecialchars($habitacion['tipo']) ?></h2>
      <p class="precio"><strong>Precio:</strong> $<?= number_format($habitacion['precio'], 2) ?></p>
      <p><strong>Estado:</strong> <?= htmlspecialchars($habitacion['estado']) ?></p>
      <p><strong>Hotel:</strong> <?= htmlspecialchars($habitacion['nombre_hotel']) ?></p>
    </div>
    <?php } ?>
  </section>
</main>

<footer class="footer">
  <p>&copy; 2025 Y Hotel. Todos los derechos reservados.</p>
</footer>

</body>
</html>
