<?php
include("../../conexion.php");
session_start();

// Verificar si hay sesión iniciada
if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: login.php");
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

// Consultar todos los huéspedes registrados
$huespedes_query = pg_query($conn, "SELECT nombre, email, telefono FROM huesped");
?>

?>



<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel Administrativo</title>
<link rel="stylesheet" href="../../../css/style_admin.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Montserrat:wght@400;700&display=swap" rel="stylesheet">

</head>

<body>

<header>
    <h1>Bienvenido, Administrador</h1>
    <p>Usuario: <strong><?= htmlspecialchars($nombre_usuario) ?></strong></p>
    <p>Nombre completo: <strong><?= htmlspecialchars($admin['nombre']) ?></strong></p>
    <p>Email: <strong><?= htmlspecialchars($admin['email']) ?></strong></p>
</header>

<center>
    <main class="tabla-container">
    <h2>Lista de Huéspedes</h2>
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($huesped = pg_fetch_assoc($huespedes_query)) : ?>
                    <tr>
                        <td><?= htmlspecialchars($huesped['nombre']) ?></td>
                        <td><?= htmlspecialchars($huesped['email']) ?></td>
                        <td><?= htmlspecialchars($huesped['telefono']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </main>
</center>

<footer>
    <a href="../panel_admin.php">Atras</a>
</footer>

</body>
</html>






