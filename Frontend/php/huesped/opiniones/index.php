<?php
include("../../conexion.php");
session_start();

if (!isset($_SESSION['rol'], $_SESSION['username'], $_SESSION['id_huesped']) || $_SESSION['rol'] !== 'huesped') {
    header("Location: ../../../login/login.php");
    exit();
}

$id_huesped = $_SESSION['id_huesped'];

// Obtener opiniones del huésped logueado
$query = pg_query_params($conn, "
    SELECT id_opinion, comentario, calificacion, fecha
    FROM opinion
    WHERE id_huesped = $1
    ORDER BY fecha DESC
", array($id_huesped));

if (!$query) {
    echo "<p>Error al cargar las opiniones.</p>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Mis Opiniones</title>
    <link rel="stylesheet" href="../../../css/style_opiniones.css" />
</head>
<body>

<div class="crud-container">
    <header class="crud-header">
        <h1>Mis Opiniones</h1>
        <p>Hola, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
    </header>

    <main>
        <a href="insert.php" class="btn btn-crear">+ Nueva Opinión</a>

        <div class="tabla-container">
        <?php if (pg_num_rows($query) === 0): ?>
            <p>No has dejado ninguna opinión aún.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Comentario</th>
                        <th>Calificación</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($opinion = pg_fetch_assoc($query)): ?>
                    <tr>
                        <td><?= $opinion['id_opinion'] ?></td>
                        <td><?= htmlspecialchars($opinion['comentario']) ?></td>
                        <td><?= $opinion['calificacion'] ?></td>
                        <td><?= $opinion['fecha'] ?></td>
                        <td>
                            <a href="update.php?id=<?= $opinion['id_opinion'] ?>" class="btn btn-editar">Editar</a>
                            <!-- Recomendado: eliminar con POST en producción -->
                            <a href="delete.php?id=<?= $opinion['id_opinion'] ?>" class="btn btn-eliminar" onclick="return confirm('¿Seguro que quieres eliminar esta opinión?')">Eliminar</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
        </div>
    </main>

    <footer class="crud-footer">
        <a href="/Proyecto_Hotelero/Frontend/pages/index.php" class="btn-volver">← Volver</a>
    </footer>
</div>

</body>
</html>
