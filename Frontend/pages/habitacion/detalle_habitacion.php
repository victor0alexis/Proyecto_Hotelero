<?php
include("../../php/conexion.php");
session_start();

// Verificar rol de huésped
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

// Validar ID de habitación
if (!isset($_GET['id'])) {
    header("Location: habitaciones.php");
    exit();
}

$id_habitacion = intval($_GET['id']);

// Obtener datos de la habitación
$consulta = pg_query_params($conn, "
    SELECT 
        h.ID_Habitacion AS id_habitacion,
        h.Tipo AS tipo,
        h.Precio AS precio,
        h.Estado AS estado,
        h.Imagen AS imagen,
        h.Descripcion AS descripcion,
        ht.Nombre AS nombre_hotel
    FROM Habitacion h
    JOIN Hotel ht ON h.ID_Hotel = ht.ID_Hotel
    WHERE h.ID_Habitacion = $1
", array($id_habitacion));

$habitacion = pg_fetch_assoc($consulta);
if (!$habitacion) {
    echo "<p>Habitación no encontrada.</p>";
    exit();
}

// Consulta de servicios incluidos para la habitación con descripción y costo correctos según tipo
// Obtener servicios transporte
$servicios_transporte = pg_query_params($conn, "
    SELECT si.Tipo_Servicio, si.Personal_Encargado, st.Descripcion, st.Costo
    FROM Servicio_Incluido si
    JOIN Servicio_Transporte st ON si.ID_Servicio = st.ID_Servicio_Transporte
    WHERE si.Tipo_Servicio = 'transporte' AND si.ID_Habitacion = $1
", [$id_habitacion]);

// Obtener servicios lavanderia
$servicios_lavanderia = pg_query_params($conn, "
    SELECT si.Tipo_Servicio, si.Personal_Encargado, sl.Descripcion, sl.Costo
    FROM Servicio_Incluido si
    JOIN Servicio_Lavanderia sl ON si.ID_Servicio = sl.ID_Servicio_Lavanderia
    WHERE si.Tipo_Servicio = 'lavanderia' AND si.ID_Habitacion = $1
", [$id_habitacion]);

// Obtener servicios habitacion
$servicios_habitacion = pg_query_params($conn, "
    SELECT si.Tipo_Servicio, si.Personal_Encargado, sh.Descripcion, sh.Costo
    FROM Servicio_Incluido si
    JOIN Servicio_Habitacion sh ON si.ID_Servicio = sh.ID_Servicio_Habitacion
    WHERE si.Tipo_Servicio = 'habitacion' AND si.ID_Habitacion = $1
", [$id_habitacion]);

// Mezclar todos los servicios en un solo array
$servicios_incluidos = [];

while ($row = pg_fetch_assoc($servicios_transporte)) {
    $servicios_incluidos[] = $row;
}
while ($row = pg_fetch_assoc($servicios_lavanderia)) {
    $servicios_incluidos[] = $row;
}
while ($row = pg_fetch_assoc($servicios_habitacion)) {
    $servicios_incluidos[] = $row;
}


?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de Habitación</title>
    <link rel="stylesheet" href="../../css/Habitacion/style_detalle_habitaciones.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
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
          <a href="../../php/huesped/datos_huesped.php">Datos</a>
          <a href="../../php/huesped/reservas_hechas.php">Reservas hechas</a>
          <a href="../../php/cambiar_password.php">Cambio de contraseña</a>
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


<!-- ======= DETALLES HABITACIONES ======= -->


<main class="detalle-container">
    <h1><?= htmlspecialchars($habitacion['tipo']) ?> - <?= htmlspecialchars($habitacion['nombre_hotel']) ?></h1>

    <div class="detalle-contenido">
        <div class="imagen">
            <img src="../../img/habitaciones/<?= htmlspecialchars($habitacion['imagen']) ?: 'default.jpg' ?>" alt="Habitación <?= htmlspecialchars($habitacion['tipo']) ?>">
        </div>
        <div class="info">
            <p><strong>Precio:</strong> $<?= number_format($habitacion['precio'], 3) ?> por noche</p>
            <p><strong>Estado:</strong> <?= htmlspecialchars($habitacion['estado']) ?></p>
            <p><strong>Descripción:</strong> <?= htmlspecialchars($habitacion['descripcion']) ?: 'Sin descripción disponible.' ?></p>

            

            <?php if (strtolower($habitacion['estado']) === 'disponible'): ?>
                <?php
                $url_formulario = '/Proyecto_Hotelero/Frontend/pages/reservas/reserva_formulario.php?id=' . $habitacion['id_habitacion'];
                if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'huesped') {
                    $url_login = '../../php/login/login.php?redirect=' . urlencode($url_formulario);
                ?>
                    <a href="<?= $url_login ?>" class="btn-reservar">Reservar Ahora</a>
                <?php } else { ?>
                    <a href="<?= $url_formulario ?>" class="btn-reservar">Reservar Ahora</a>
                <?php } ?>
            <?php else: ?>
                <p class="no-disponible">Esta habitación no está disponible actualmente.</p>
            <?php endif; ?>
            <a href="habitaciones.php" class="btn-reservar">Volver Habitaciones</a>
        </div>
    </div>
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
