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
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <header>
        <h1>Bienvenido, Administrador</h1>
        <p>Usuario: <strong><?= htmlspecialchars($nombre_usuario) ?></strong></p>
        <p>Nombre: <strong><?= htmlspecialchars($admin['nombre']) ?></strong></p>
        <p>Email: <strong><?= htmlspecialchars($admin['email']) ?></strong></p>
    </header>

    <main>
        <div class="logo">Panel de Control</div>
        <ul>
            <center>
            <li><a href="CRUD/huesped/index.php">Gestionar Huespedes</a></li>
            <li><a href="CRUD/reservas.php">Gestionar Reservas</a></li>
            <li><a href="CRUD/habitaciones.php">Gestionar Habitaciones</a></li>
            <li><a href="CRUD/servicios.php">Gestionar Servicios del Hotel</a></li>
            </center>
        </ul>
    </main>

    <footer>
        <a href="../login/logout.php">Cerrar sesión</a>
    </footer>

</body>
</html>
