<?php
include("../../../conexion.php");
session_start();

if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');

    // Validaciones del lado servidor
    if (strlen($nombre) < 3 || !preg_match('/^[\p{L}\s]+$/u', $nombre)) {
        $errores[] = "El nombre debe tener al menos 3 letras y solo contener letras y espacios.";
    }

    if (strlen($direccion) < 10) {
        $errores[] = "La dirección debe tener al menos 10 caracteres.";
    }

    if (!preg_match('/^\d{9,11}$/', $telefono)) {
        $errores[] = "El teléfono debe contener solo números (entre 9 y 11 dígitos).";
    }

    if (empty($errores)) {
        $query = pg_query_params($conn, "
            INSERT INTO hotel (nombre, direccion, telefono)
            VALUES ($1, $2, $3)
        ", array($nombre, $direccion, $telefono));

        if ($query) {
            header("Location: index.php");
            exit();
        } else {
            $errores[] = "Error en la base de datos al insertar el hotel.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Añadir Hotel</title>
    <link rel="stylesheet" href="../../../../css/CRUD/style_crud_insert.css">
</head>
<body>

<div class="form-container">
    <h2>Registrar Nuevo Hotel</h2>

    <?php if (!empty($errores)): ?>
        <div class="errores">
            <ul>
                <?php foreach ($errores as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post">
        <label for="nombre">Nombre del Hotel:</label>
        <input type="text" name="nombre" id="nombre" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required minlength="3" pattern="[A-Za-zÁÉÍÓÚáéíóúñÑ\s]+" title="Solo letras y espacios.">

        <label for="direccion">Dirección:</label>
        <input type="text" name="direccion" id="direccion" value="<?= htmlspecialchars($_POST['direccion'] ?? '') ?>" required minlength="10">

        <label for="telefono">Teléfono:</label>
        <input type="text" name="telefono" id="telefono" value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>" pattern="^\d{9,11}$" title="Debe tener entre 9 y 11 dígitos numéricos." required>

        <div class="form-buttons">
            <button type="submit">Guardar</button>
            <a href="index.php" class="btn-volver">Volver</a>
        </div>
    </form>
</div>

</body>
</html>
