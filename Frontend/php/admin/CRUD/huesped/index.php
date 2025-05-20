<?php
include("../../../conexion.php");
session_start();

// Solo permitir acceso a administradores
if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

// Obtener lista de huéspedes
$query = pg_query($conn, "
    SELECT u.id_usuario, u.username, h.nombre, h.email, h.telefono, h.verificado 
    FROM huesped h
    JOIN usuario u ON h.id_usuario = u.id_usuario
    ORDER BY h.nombre
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Huéspedes</title>
    <link rel="stylesheet" href="../../../../css/CRUD/style_crud_index.css">
</head>
<body>

<div class="crud-container">
    <header class="crud-header">
        <h1>Gestión de Huéspedes</h1>
        <p>Bienvenido, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
    </header>

    <main>
        <a href="insert.php" class="btn btn-crear">+ Añadir Huésped</a>

        <div class="tabla-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Verificado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($huesped = pg_fetch_assoc($query)): ?>
                        <tr>
                            <td><?= $huesped['id_usuario'] ?></td>
                            <td><?= htmlspecialchars($huesped['username']) ?></td>
                            <td><?= htmlspecialchars($huesped['nombre']) ?></td>
                            <td><?= htmlspecialchars($huesped['email']) ?></td>
                            <td><?= htmlspecialchars($huesped['telefono']) ?></td>
                            <td><?= $huesped['verificado'] === 't' ? 'Sí' : 'No' ?></td>
                            <td>
                                <a href="update.php?id=<?= $huesped['id_usuario'] ?>" class="btn btn-editar">Editar</a>
                                <a href="delete.php?eliminar=<?= $huesped['id_usuario'] ?>" class="btn btn-eliminar" onclick="return confirm('¿Estás seguro de eliminar este huésped?')">Eliminar</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>

    <footer class="crud-footer">
        <a href="../../panel_admin.php" class="btn-volver">← Volver al panel</a>
    </footer>
</div>

</body>
</html>
