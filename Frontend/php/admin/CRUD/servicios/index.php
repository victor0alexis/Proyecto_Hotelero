<?php
include("../../../conexion.php");
session_start();

// Solo permitir acceso a administradores
if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

// Obtener lista de servicios con ID del servicio específico para ordenar
$query = pg_query($conn, "
    SELECT 
        si.id_servicio_incluido,
        si.tipo_servicio,
        si.personal_encargado,
        si.id_habitacion,
        si.id_reserva,
        -- Obtener la descripción y costo según el tipo
        CASE 
            WHEN si.tipo_servicio = 'transporte' THEN st.descripcion
            WHEN si.tipo_servicio = 'lavanderia' THEN sl.descripcion
            WHEN si.tipo_servicio = 'habitacion' THEN sh.descripcion
        END AS descripcion,
        CASE 
            WHEN si.tipo_servicio = 'transporte' THEN st.costo
            WHEN si.tipo_servicio = 'lavanderia' THEN sl.costo
            WHEN si.tipo_servicio = 'habitacion' THEN sh.costo
        END AS costo,
        -- Capturar el ID real del servicio según tipo
        CASE 
            WHEN si.tipo_servicio = 'transporte' THEN st.id_servicio_transporte
            WHEN si.tipo_servicio = 'lavanderia' THEN sl.id_servicio_lavanderia
            WHEN si.tipo_servicio = 'habitacion' THEN sh.id_servicio_habitacion
        END AS id_servicio_especifico
    FROM servicio_incluido si
    LEFT JOIN servicio_transporte st ON si.id_servicio = st.id_servicio_transporte AND si.tipo_servicio = 'transporte'
    LEFT JOIN servicio_lavanderia sl ON si.id_servicio = sl.id_servicio_lavanderia AND si.tipo_servicio = 'lavanderia'
    LEFT JOIN servicio_habitacion sh ON si.id_servicio = sh.id_servicio_habitacion AND si.tipo_servicio = 'habitacion'
    ORDER BY si.tipo_servicio, id_servicio_especifico ASC
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Servicios del Hotel</title>
    <link rel="stylesheet" href="../../../../css/CRUD/style_crud_index.css">
</head>
<body>

<div class="crud-container">
    <header class="crud-header">
        <h1>Gestión de Servicios del Hotel</h1>
        <p>Bienvenido, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
    </header>

    <main>
        <div class="btn-group">
            <a href="insert.php" class="btn btn-crear">+ Añadir Servicio</a>
            <a href="insert.php?tipo=transporte" class="btn btn-transporte">+ Transporte</a>
            <a href="insert.php?tipo=lavanderia" class="btn btn-lavanderia">+ Lavandería</a>
            <a href="insert.php?tipo=habitacion" class="btn btn-habitacion">+ Habitación</a>
        </div>

        <div class="tabla-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th>Costo</th>
                        <th>Encargado</th>
                        <th>Habitación</th>
                        <th>Reserva</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($servicio = pg_fetch_assoc($query)): ?>
                        <tr>
                            <td><?= $servicio['id_servicio_incluido'] ?></td>
                            <td><?= ucfirst(htmlspecialchars($servicio['tipo_servicio'])) ?></td>
                            <td><?= htmlspecialchars($servicio['descripcion']) ?></td>
                            <td><?= number_format($servicio['costo'], 3) ?></td>
                            <td><?= htmlspecialchars($servicio['personal_encargado']) ?></td>
                            <td><?= $servicio['id_habitacion'] ?? 'N/A' ?></td>
                            <td><?= $servicio['id_reserva'] ?? 'N/A' ?></td>
                            <td>
                                <a href="update.php?id=<?= $servicio['id_servicio_incluido'] ?>" class="btn btn-editar">Editar</a>
                                <a href="delete.php?eliminar=<?= $servicio['id_servicio_incluido'] ?>" class="btn btn-eliminar" onclick="return confirm('¿Estás seguro de eliminar este servicio?')">Eliminar</a>
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
