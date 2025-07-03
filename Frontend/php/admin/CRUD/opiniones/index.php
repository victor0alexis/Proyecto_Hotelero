<?php
include("../../../conexion.php");
session_start();

// Verificar que es un admin
if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

// Consulta: obtener opiniones con nombre de huésped
$query = pg_query($conn, "
    SELECT o.id_opinion, o.comentario, o.calificacion, o.fecha,
           h.nombre AS nombre_huesped
    FROM opinion o
    JOIN huesped h ON o.id_huesped = h.id_huesped
    ORDER BY o.fecha DESC
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Opiniones</title>
    <link rel="stylesheet" href="../../../../css/CRUD/style_crud_index.css">
</head>
<body>

<div class="crud-container">
    <header class="crud-header">
        <h1>Gestión de Opiniones</h1>
        <p>Bienvenido, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
    </header>

    <main>
        <a href="insert.php" class="btn btn-crear">+ Añadir Opinión</a>

        <div class="tabla-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Huésped</th>
                        <th>Comentario</th>
                        <th>Calificación</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($op = pg_fetch_assoc($query)): ?>
                        <tr>
                            <td><?= $op['id_opinion'] ?></td>
                            <td><?= htmlspecialchars($op['nombre_huesped']) ?></td>
                            <td><?= htmlspecialchars($op['comentario']) ?></td>
                            <td><?= $op['calificacion'] ?> ⭐</td>
                            <td><?= date('d/m/Y H:i', strtotime($op['fecha'])) ?></td>
                            <td>
                                <a href="update.php?id=<?= $op['id_opinion'] ?>" class="btn btn-editar">Editar</a>
                                <a href="delete.php?id=<?= $op['id_opinion'] ?>" class="btn btn-eliminar" onclick="return confirm('¿Eliminar esta opinión?')">Eliminar</a>
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
