<?php
include("../conexion.php");
session_start();

// Verificar si hay sesión iniciada
if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../login/login.php");
    exit();
}

// Obtener información del administrador
$nombre_usuario = $_SESSION['username'];
$consulta = pg_query_params($conn, 
    "SELECT a.nombre, a.email 
     FROM administrador a
     JOIN usuario u ON a.id_usuario = u.id_usuario
     WHERE u.username = $1", 
    array($nombre_usuario)
);

$admin = pg_fetch_assoc($consulta);
?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel Administrativo</title>
<link rel="stylesheet" href="../../css/style_admin.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Montserrat:wght@400;700&display=swap" rel="stylesheet">

</head>

<body>

<header>
    <h1>Bienvenido Administrador</h1>
    <p>Usuario: <strong><?= htmlspecialchars($nombre_usuario) ?></strong></p>
    <p>Nombre completo: <strong><?= htmlspecialchars($admin['nombre']) ?></strong></p>
    <p>Email: <strong><?= htmlspecialchars($admin['email']) ?></strong></p>
</header>


<center>

<main>
    <div class="logo"> Panel Administrativo </div>
        <ul>
            <li><a href="funcionalidades/huespedes.php">Ver Huéspedes Registrados</a></li>
            <li><a href="gestionar_reservas.php">Gestionar Reservas</a></li>
            <li><a href="habitaciones.php">Gestionar Habitaciones</a></li>
            <li><a href="servicios.php">Servicios del Hotel</a></li>
        </ul>
</main>

</center>

<footer>
    <a href="../login/logout.php">Cerrar sesión</a>
</footer>

</body>
</html>






