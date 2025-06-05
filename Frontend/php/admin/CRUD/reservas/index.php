<?php
include("../../../conexion.php");
session_start();

// Solo permitir acceso a administradores
if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

// Obtener lista de reservas con datos de huésped y habitación
$query = pg_query($conn, "
    SELECT r.id_reserva, r.fecha_entrada, r.fecha_salida, r.estado,
           h.nombre AS nombre_huesped,
           hab.tipo AS tipo_habitacion
    FROM reserva r
    JOIN huesped h ON r.id_huesped = h.id_huesped
    JOIN habitacion hab ON r.id_habitacion = hab.id_habitacion
    ORDER BY r.fecha_entrada DESC
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Reservas</title>
    <link rel="stylesheet" href="../../../../css/CRUD/style_crud_index.css">
</head>
<body>

<div class="crud-container">
    <header class="crud-header">
        <h1>Gestión de Reservas</h1>
        <p>Bienvenido, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
    </header>

    <main>
        <a href="insert.php" class="btn btn-crear">+ Añadir Reserva</a>

        <div class="tabla-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Huésped</th>
                        <th>Habitación</th>
                        <th>Entrada</th>
                        <th>Salida</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($reserva = pg_fetch_assoc($query)): ?>
                        <tr>
                            <td><?= $reserva['id_reserva'] ?></td>
                            <td><?= htmlspecialchars($reserva['nombre_huesped']) ?></td>
                            <td><?= htmlspecialchars($reserva['tipo_habitacion']) ?></td>
                            <td><?= htmlspecialchars($reserva['fecha_entrada']) ?></td>
                            <td><?= htmlspecialchars($reserva['fecha_salida']) ?></td>
                            <td><?= ucfirst(htmlspecialchars($reserva['estado'])) ?></td>
                            <td>
                                <a href="update.php?id=<?= $reserva['id_reserva'] ?>" class="btn btn-editar">Editar</a>
                                <a href="delete.php?id=<?= $reserva['id_reserva'] ?>" class="btn btn-eliminar" onclick="return confirm('¿Estás seguro de eliminar esta reserva?')">Eliminar</a>
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
