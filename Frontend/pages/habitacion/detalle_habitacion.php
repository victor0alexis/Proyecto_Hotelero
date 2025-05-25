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

// Validar que se envíe un ID de habitación
if (!isset($_GET['id'])) {
    header("Location: habitaciones.php");
    exit();
}

$id_habitacion = intval($_GET['id']);

// Consulta de los datos de la habitación
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

// Si no se encuentra la habitación
if (!$habitacion) {
    echo "<p>Habitación no encontrada.</p>";
    exit();
}

// Consulta de servicios incluidos para la habitación
$consulta_servicios = pg_query_params($conn, "
    SELECT si.Tipo_Servicio, 
           CASE si.Tipo_Servicio
               WHEN 'Lavandería' THEN sl.Descripcion
               WHEN 'Habitación' THEN sh.Descripcion
               WHEN 'Transporte' THEN st.Descripcion
               ELSE 'Sin descripción'
           END AS Descripcion
    FROM Servicio_Incluido si
    LEFT JOIN Servicio_Lavanderia sl ON si.Tipo_Servicio = 'Lavandería' AND si.ID_Servicio = sl.ID_Servicio_Lavanderia
    LEFT JOIN Servicio_Habitacion sh ON si.Tipo_Servicio = 'Habitación' AND si.ID_Servicio = sh.ID_Servicio_Habitacion
    LEFT JOIN Servicio_Transporte st ON si.Tipo_Servicio = 'Transporte' AND si.ID_Servicio = st.ID_Servicio_Transporte
    WHERE si.ID_Habitacion = $1
", array($id_habitacion));



$servicios_incluidos = [];
while ($row = pg_fetch_assoc($consulta_servicios)) {
    $servicios_incluidos[] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de Habitación</title>
    <link rel="stylesheet" href="../../css/Habitaciones/style_detalle_habitacion.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
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


<!-- ======= SECCION PRINCIPAL ======= -->

<main class="detalle-container">
    <h1><?= htmlspecialchars($habitacion['tipo']) ?> - <?= htmlspecialchars($habitacion['nombre_hotel']) ?></h1>

    <div class="detalle-contenido">
        <div class="imagen">
            <img src="../../img/habitaciones/<?= htmlspecialchars($habitacion['imagen']) ?: 'default.jpg' ?>" alt="Habitación <?= htmlspecialchars($habitacion['tipo']) ?>">
        </div>
        <div class="info">
            <p><strong>Precio:</strong> $<?= number_format($habitacion['precio'], 2) ?> por noche</p>
            <p><strong>Estado:</strong> <?= htmlspecialchars($habitacion['estado']) ?></p>
            <p><strong>Descripción:</strong> <?= htmlspecialchars($habitacion['descripcion']) ?: 'Sin descripción disponible.' ?></p>

            <?php if (!empty($servicios_incluidos)): ?>
                <div class="servicios-incluidos">
                    <h3>Servicios Incluidos:</h3>
                    <ul>
                        <?php foreach ($servicios_incluidos as $servicio): ?>
                            <li><strong><?= htmlspecialchars($servicio['tipo_servicio']) ?>:</strong> <?= htmlspecialchars($servicio['descripcion']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <p><em>No hay servicios incluidos registrados para esta habitación.</em></p>
            <?php endif; ?>

        <!-- ======= Boton "Reservar Ahora" ======= -->
        <?php if (strtolower($habitacion['estado']) === 'disponible'): ?>
            <?php
            $url_formulario = '/Proyecto_Hotelero/Frontend/pages/habitacion/reserva_formulario.php?id=' . $habitacion['id_habitacion'];

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

        </div>
    </div>
</main>

<footer class="footer">
    <p>&copy; 2025 Y Hotel. Todos los derechos reservados.</p>
</footer>

</body>
</html>
