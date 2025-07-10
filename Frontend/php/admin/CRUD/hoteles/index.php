<?php
include("../../../conexion.php");
session_start();

if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

// Obtener hoteles
$consulta = pg_query($conn, "SELECT * FROM hotel ORDER BY id_hotel ASC");

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Hoteles</title>
    <link rel="stylesheet" href="../../../../css/CRUD/style_crud_index.css">
</head>
<body>
<div class="crud-container">

    <header class="crud-header">

    <h1>Gestión de Hoteles</h1>
    <p>Bienvenido, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>

    </header>

    <a href="insert.php" class="btn btn-crear">➕ Añadir Hotel</a>

    <?php if (pg_num_rows($consulta) === 0): ?>
        <p>No hay hoteles registrados.</p>
    <?php else: ?>
<div class="tabla-container">
        <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Dirección</th>
                <th>Teléfono</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($hotel = pg_fetch_assoc($consulta)): ?>
                <tr>
                    <td><?= htmlspecialchars($hotel['id_hotel']) ?></td>
                    <td><?= htmlspecialchars($hotel['nombre']) ?></td>
                    <td><?= htmlspecialchars($hotel['direccion']) ?></td>
                    <td><?= htmlspecialchars($hotel['telefono']) ?: '-' ?></td>
                    <td>
                        <a href="update.php?id=<?= $hotel['id_hotel'] ?>" class="btn btn-editar">✏️ Editar</a>
                        <a href="delete.php?id=<?= $hotel['id_hotel'] ?>" class="btn btn-eliminar" onclick="return confirm('¿Estás seguro de eliminar este hotel?');">🗑️ Eliminar</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
    <?php endif; ?>
</div>

    <footer class="crud-footer">
        <a href="../../panel_admin.php" class="btn-volver">← Volver al panel</a>
    </footer>

</body>
</html>
