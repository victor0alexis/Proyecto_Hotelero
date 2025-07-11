
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
        h.estado_actividad AS actividad,
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

// Obtener servicios
$servicios_transporte = pg_query_params($conn, "
    SELECT si.Tipo_Servicio, si.Personal_Encargado, st.Descripcion, st.Costo
    FROM Servicio_Incluido si
    JOIN Servicio_Transporte st ON si.ID_Servicio = st.ID_Servicio_Transporte
    WHERE si.Tipo_Servicio = 'transporte' AND si.ID_Habitacion = $1
", [$id_habitacion]);

$servicios_lavanderia = pg_query_params($conn, "
    SELECT si.Tipo_Servicio, si.Personal_Encargado, sl.Descripcion, sl.Costo
    FROM Servicio_Incluido si
    JOIN Servicio_Lavanderia sl ON si.ID_Servicio = sl.ID_Servicio_Lavanderia
    WHERE si.Tipo_Servicio = 'lavanderia' AND si.ID_Habitacion = $1
", [$id_habitacion]);

$servicios_habitacion = pg_query_params($conn, "
    SELECT si.Tipo_Servicio, si.Personal_Encargado, sh.Descripcion, sh.Costo
    FROM Servicio_Incluido si
    JOIN Servicio_Habitacion sh ON si.ID_Servicio = sh.ID_Servicio_Habitacion
    WHERE si.Tipo_Servicio = 'habitacion' AND si.ID_Habitacion = $1
", [$id_habitacion]);

$servicios_incluidos = [];
while ($row = pg_fetch_assoc($servicios_transporte)) $servicios_incluidos[] = $row;
while ($row = pg_fetch_assoc($servicios_lavanderia)) $servicios_incluidos[] = $row;
while ($row = pg_fetch_assoc($servicios_habitacion)) $servicios_incluidos[] = $row;

// Obtener opiniones
$opiniones = pg_query_params($conn, "
    SELECT o.comentario, o.calificacion, o.fecha, h.nombre
    FROM opinion o
    JOIN reserva r ON o.id_reserva = r.id_reserva
    JOIN huesped h ON o.id_huesped = h.id_huesped
    WHERE r.id_habitacion = $1
    ORDER BY o.fecha DESC
", [$id_habitacion]);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de Habitación</title>
    <link rel="stylesheet" href="../../css/Habitacion/style_detalle_habitaciones.css">
</head>
<body>
<header class="header">
    <div class="logo"><span>HOTEL</span></div>
    <nav>
        <ul class="nav-links">
            <li><a href="../index.php">Inicio</a></li>
            <li><a href="habitaciones.php" class="active">Habitaciones</a></li>
            <li><a href="../servicios/servicios.php">Servicios</a></li>
        </ul>
    </nav>
    <div class="right-nav">
        <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'huesped') : ?>
            <div class="user-dropdown">
                <button class="user-btn" onclick="toggleDropdown()">👤 <?= htmlspecialchars($_SESSION['username']) ?></button>
                <div id="userDropdown" class="dropdown-content">
                    <a href="../../php/huesped/reservas_hechas.php">Reservas hechas</a>
                    <a href="../../php/login/logout.php">Cerrar sesión</a>
                </div>
            </div>
        <?php else : ?>
            <?php $url_actual = $_SERVER['REQUEST_URI']; ?>
            <a href="../../php/login/login.php?redirect=<?= urlencode($url_actual) ?>" class="btn-login">Login ➔</a>
        <?php endif; ?>
    </div>
</header>

<main class="detalle-container">
    <h1><?= htmlspecialchars($habitacion['tipo']) ?> - <?= htmlspecialchars($habitacion['nombre_hotel']) ?></h1>
    <div class="detalle-contenido">
        <div class="imagen">
            <img src="../../img/habitaciones/<?= htmlspecialchars($habitacion['imagen']) ?: 'default.jpg' ?>" alt="Habitación <?= htmlspecialchars($habitacion['tipo']) ?>">
        </div>
        <div class="info">
            <p><strong>Precio:</strong> $<?= number_format($habitacion['precio']) ?> por noche</p>
            <p><strong>Estado:</strong> <?= htmlspecialchars($habitacion['estado']) ?></p>
            <p><strong>Descripción:</strong> <?= htmlspecialchars($habitacion['descripcion']) ?: 'Sin descripción disponible.' ?></p>

            <?php if (strtolower($habitacion['actividad']) === 'activo' && strtolower($habitacion['estado']) === 'disponible'): ?>
                <?php
                    $url_formulario = '/Proyecto_Hotelero/Frontend/pages/reservas/reserva_formulario.php?id=' . $habitacion['id_habitacion'];
                    $url_login = '../../php/login/login.php?redirect=' . urlencode($url_formulario);
                ?>
                <?php if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'huesped') : ?>
                    <a href="<?= $url_login ?>" class="btn-reservar">Reservar Ahora</a>
                <?php else : ?>
                    <a href="<?= $url_formulario ?>" class="btn-reservar">Reservar Ahora</a>
                <?php endif; ?>
            <?php else : ?>
                <p class="no-disponible">Esta habitación no está disponible actualmente.</p>
            <?php endif; ?>

            <a href="habitaciones.php" class="btn-reservar">Volver a Habitaciones</a>
        </div>
    </div>

    <?php if (pg_num_rows($opiniones) > 0): ?>
        <section class="opiniones-habitacion">
            <h2>Opiniones de Huéspedes</h2>
            <?php while ($op = pg_fetch_assoc($opiniones)) : ?>
                <div class="opinion-card">
                    <p><strong><?= htmlspecialchars($op['nombre']) ?></strong> - <?= htmlspecialchars($op['fecha']) ?></p>
                    <p><em>Calificación:</em> <?= str_repeat('⭐', (int)$op['calificacion']) ?> (<?= $op['calificacion'] ?>/5)</p>
                    <p>"<?= htmlspecialchars($op['comentario']) ?>"</p>
                </div>
            <?php endwhile; ?>
        </section>
    <?php endif; ?>
</main>

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

<footer class="footer">
    <p>&copy; 2025 Hotel. Todos los derechos reservados.</p>
</footer>
</body>
</html>




