<?php
session_start();
include("../conexion.php");

// Verificación de sesión de huésped
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'huesped') {
    header("Location: ../login/login.php");
    exit();
}

$id_huesped = $_SESSION['id_huesped'] ?? null;

if (!$id_huesped) {
    echo "<p>Sesión inválida: ID del huésped no disponible.</p>";
    exit();
}

// Consulta principal de reservas
$reservas = pg_query_params($conn, "
    SELECT r.id_reserva, r.fecha_entrada, r.fecha_salida, r.estado,
        h.tipo AS tipo_habitacion, h.Imagen, h.precio,
        b.monto, b.estado_pago
    FROM reserva r
    JOIN habitacion h ON r.id_habitacion = h.id_habitacion
    LEFT JOIN boleta b ON b.id_reserva = r.id_reserva
    WHERE r.id_huesped = $1
    ORDER BY r.fecha_entrada DESC
", [$id_huesped]);



if (!$reservas) {
    echo "<p>Error en la consulta: " . pg_last_error($conn) . "</p>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Reservas</title>
    <link rel="stylesheet" href="../../css/Reserva/style_mis_reservas.css">
</head>



<!-- ======= HEADER ======= -->

<header class="header">
<div class="logo"><span>Hotel</span></div>

<!-- Parte izquierda Barra Navegacion-->
<ul class="nav-links">
    <li><a href="../../pages/index.php">Inicio</a></li>
    <li><a href="../../pages/habitacion/habitaciones.php">Habitaciones</a></li>
    <li><a href="../../pages/servicios/servicios.php">Servicios</a></li>
    <li><a href="../../pages/contacto.php">Contacto</a></li>
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
        <a href="datos_huesped.php">Datos</a>
        <a href="reservas_hechas.php">Reservas hechas</a>
        <a href="cambiar_password.php">Cambio de contraseña</a>
        <a href="../login/logout.php">Cerrar sesión</a>
        </div>
    </div>
    <!-- Si el usuario no ha iniciado sesion.-->
    <?php else : ?>
    <!-- se guarda "url_actual"-->
    <?php $url_actual = $_SERVER['REQUEST_URI']; ?>    
    <!-- se redirige a Login.php, con la URL codificada como parametro redirect"-->
    <a href="../login/login.php?redirect=<?= urlencode($url_actual) ?>" class="btn-login">Login ➔</a>
    <?php endif; ?>
</div>

</header>


<!-- ======= BODY ======= -->

<body>

<!-- ======= SECCION PRINCIPAL DE RESERVAS ======= -->

    <div class="contenedor-reservas">

    <h1>Mis Reservas</h1>

<?php if (pg_num_rows($reservas) === 0): ?>
    <p>No tienes reservas registradas.</p>

<?php else: ?>

    <?php while ($row = pg_fetch_assoc($reservas)): ?>

    <?php
        // Obtenemos el nombre de la imagen (campo "Imagen" de la tabla Habitacion)
        $imagenHabitacion = !empty($row['imagen']) ? $row['imagen'] : 'default.jpg';
        $rutaImagen = "/Proyecto_Hotelero/Frontend/img/habitaciones/" . $imagenHabitacion;
    ?>

    <div class="reserva-card">

        <div class="reserva-imagen" style="background-image: url('<?= htmlspecialchars($rutaImagen) ?>');">
        </div>

        <div class="reserva-detalle">

            <div>       
                <div class="reserva-header">
                <h2>Reserva #<?= htmlspecialchars($row['id_reserva']) ?></h2>
                <span class="estado estado-<?= htmlspecialchars(strtolower($row['estado'])) ?>">
                    <?= ucfirst(htmlspecialchars($row['estado'])) ?>
                </span>
                </div>

                <p class="reserva-info"><strong>Habitación:</strong> <?= htmlspecialchars($row['tipo_habitacion']) ?></p>
                <p class="reserva-info"><strong>Entrada:</strong> <?= htmlspecialchars($row['fecha_entrada']) ?></p>
                <p class="reserva-info"><strong>Salida:</strong> <?= htmlspecialchars($row['fecha_salida']) ?></p>
                <p class="reserva-info"><strong>Monto pagado:</strong> <?= ($row['monto']) ? '$' . number_format($row['monto'], 3) : '-' ?></p>
            </div>




        <?php if ($row['estado'] === 'pendiente'): ?>
        <a href="../../pages/reservas/pago_reserva.php?id=<?= $row['id_reserva'] ?>" class="btn-boleta">Pagar Ahora</a>
        <?php endif ?>
        
        <?php if ($row['estado'] === 'confirmada'): ?>
        <a href="boleta.php?id_reserva=<?= urlencode($row['id_reserva']) ?>" class="btn-boleta">Obtener Boleta</a>
        <?php endif ?>
        



        </div>

    </div>

    <?php endwhile; ?>

<?php endif; ?>

</div>




<!-- ======= SCRIPT FUNCIONALIDAD BOTON. ======= -->


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
