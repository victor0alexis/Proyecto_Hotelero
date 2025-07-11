<?php
include("../../../conexion.php");
session_start();

if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

$query = pg_query($conn, "
    SELECT mp.id_metodo_pago, mp.nombre_titular, mp.nombre_metodo, mp.numero_operacion, b.id_boleta, b.monto
    FROM Metodo_Pago mp
    JOIN Boleta b ON mp.id_boleta = b.id_boleta
    ORDER BY mp.id_metodo_pago
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Métodos de Pago</title>
    <link rel="stylesheet" href="../../../../css/CRUD/style_crud_index.css">
</head>
<body>

<div class="crud-container">
    <header class="crud-header">
        <h1>Gestión de Métodos de Pago</h1>
        <p>Bienvenido, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
    </header>

    <main>
        <a href="insert.php" class="btn btn-crear">+ Añadir Método de Pago</a>

        <div class="tabla-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titular</th>
                        <th>Método</th>
                        <th>N° Operación</th>
                        <th>ID Boleta</th>
                        <th>Monto</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = pg_fetch_assoc($query)): ?>
                        <tr>
                            <td><?= $row['id_metodo_pago'] ?></td>
                            <td><?= htmlspecialchars($row['nombre_titular']) ?></td>
                            <td><?= htmlspecialchars($row['nombre_metodo']) ?></td>
                            <td><?= htmlspecialchars($row['numero_operacion']) ?></td>
                            <td><?= $row['id_boleta'] ?></td>
                            <td>$<?= $row['monto'] ?></td>
                            <td>
                                <a href="update.php?id=<?= $row['id_metodo_pago'] ?>" class="btn btn-editar">Editar</a>
                                <a href="delete.php?eliminar=<?= $row['id_metodo_pago'] ?>" class="btn btn-eliminar" onclick="return confirm('¿Eliminar este método de pago?')">Eliminar</a>
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
