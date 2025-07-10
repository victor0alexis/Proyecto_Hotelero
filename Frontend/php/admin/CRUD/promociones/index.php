<?php
include("../../../conexion.php");
session_start();

if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

$query = pg_query($conn, "SELECT * FROM Promocion ORDER BY ID_Promocion");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Promociones</title>
    <link rel="stylesheet" href="../../../../css/CRUD/style_crud_index.css">
</head>
<body>

<div class="crud-container">
    <header class="crud-header">
        <h1>Gestión de Promociones</h1>
        <p>Bienvenido, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
    </header>

    <main>
        <a href="insert.php" class="btn btn-crear">+ Añadir Promoción</a>

        <div class="tabla-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Descripción</th>
                        <th>Descuento (%)</th>
                        <th>Inicio</th>
                        <th>Fin</th>
                        <th>Estado</th>
                        <th>Imagen</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($promo = pg_fetch_assoc($query)): ?>
                        <tr>
                            <td><?= $promo['id_promocion'] ?></td>
                            <td><?= htmlspecialchars($promo['titulo']) ?></td>
                            <td><?= htmlspecialchars($promo['descripcion']) ?></td>
                            <td><?= $promo['descuento'] ?>%</td>
                            <td><?= $promo['fecha_inicio'] ?></td>
                            <td><?= $promo['fecha_fin'] ?></td>
                            <td><?= htmlspecialchars($promo['estado']) ?></td>
                            <td>
                                <?php if (!empty($promo['imagen'])): ?>
                                    <img src="../../../../img/promociones/<?= htmlspecialchars($promo['imagen']) ?>" width="80">
                                <?php else: ?>
                                    Sin imagen
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="update.php?id=<?= $promo['id_promocion'] ?>" class="btn btn-editar">Editar</a>
                                <a href="delete.php?eliminar=<?= $promo['id_promocion'] ?>" class="btn btn-eliminar" onclick="return confirm('¿Eliminar esta promoción?')">Eliminar</a>
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
