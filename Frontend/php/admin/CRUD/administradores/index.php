<?php
include("../../../conexion.php");
session_start();

// Solo permitir acceso a administradores
if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

// Obtener lista de administradores con datos de usuario
$query = pg_query($conn, "
    SELECT a.id_admin, u.id_usuario, u.username, a.nombre, a.email, a.verificado
    FROM administrador a
    JOIN usuario u ON a.id_usuario = u.id_usuario
    ORDER BY a.nombre
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Administradores</title>
    <link rel="stylesheet" href="../../../../css/CRUD/style_crud_index.css">
</head>
<body>

<div class="crud-container">
    <header class="crud-header">
        <h1>Gestión de Administradores</h1>
        <p>Bienvenido, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
    </header>

    <main>
        <a href="insert.php" class="btn btn-crear">+ Añadir Administrador</a>

        <div class="tabla-container">
            <table>
                <thead>
                    <tr>
                        <th>ID Usuario</th>
                        <th>Username</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Verificado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($admin = pg_fetch_assoc($query)): ?>
                        <tr>
                            <td><?= $admin['id_usuario'] ?></td>
                            <td><?= htmlspecialchars($admin['username']) ?></td>
                            <td><?= htmlspecialchars($admin['nombre']) ?></td>
                            <td><?= htmlspecialchars($admin['email']) ?></td>
                            <td><?= $admin['verificado'] === 't' ? 'Sí' : 'No' ?></td>
                            <td>
                                <a href="update.php?id=<?= $admin['id_usuario'] ?>" class="btn btn-editar">Editar</a>
                                <a href="delete.php?eliminar=<?= $admin['id_usuario'] ?>" class="btn btn-eliminar" onclick="return confirm('¿Estás seguro de eliminar este administrador?')">Eliminar</a>
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
