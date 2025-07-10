<?php
include("../../../conexion.php");
session_start();

// Solo permitir acceso a administradores
if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

// Obtener lista de habitaciones
$query = pg_query($conn, "
    SELECT h.id_habitacion, h.precio, h.estado, h.estado_actividad, h.tipo, h.descripcion, h.imagen, h.capacidad, ho.nombre AS hotel
    FROM habitacion h
    JOIN hotel ho ON h.id_hotel = ho.id_hotel
    ORDER BY h.id_habitacion
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Habitaciones</title>
    <link rel="stylesheet" href="../../../../css/CRUD/style_crud_index.css">
</head>
<body>

<div class="crud-container">
    <header class="crud-header">
        <h1>Gestión de Habitaciones</h1>
        <p>Bienvenido, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
    </header>

    <main>
        <a href="insert.php" class="btn btn-crear">+ Añadir Habitación</a>

        <div class="tabla-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Precio</th>
                        <th>Estado</th>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th>Imagen</th>
                        <th>Capacidad</th>
                        <th>Hotel</th>
                        <th>Estado Actividad</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($habitacion = pg_fetch_assoc($query)): ?>
                        <tr>
                            <td><?= $habitacion['id_habitacion'] ?></td>
                            <td>$<?= number_format($habitacion['precio'],) ?></td>
                            <td><?= htmlspecialchars($habitacion['estado']) ?></td>
                            <td><?= htmlspecialchars($habitacion['tipo']) ?></td>
                            <td><?= htmlspecialchars($habitacion['descripcion']) ?></td>
                            <td>
                                <?php if (!empty($habitacion['imagen'])): ?>
                                    <img src="../../../../img/habitaciones/<?= htmlspecialchars($habitacion['imagen']) ?>" width="80">
                                <?php else: ?>
                                    Sin imagen
                                <?php endif; ?>
                            </td>
                            <td><?= $habitacion['capacidad'] ?></td>
                            <td><?= htmlspecialchars($habitacion['hotel']) ?></td>
                            <td><?= htmlspecialchars($habitacion['estado_actividad']) ?></td>
                            <td>
                                <a href="update.php?id=<?= $habitacion['id_habitacion'] ?>" class="btn btn-editar">Editar</a>
                                <a href="delete.php?eliminar=<?= $habitacion['id_habitacion'] ?>" class="btn btn-eliminar" onclick="return confirm('¿Estás seguro de eliminar esta habitación?')">Eliminar</a>
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
