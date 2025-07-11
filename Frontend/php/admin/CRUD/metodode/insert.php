<?php
include("../../../conexion.php");
session_start();

if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

$mensaje = "";
$nombre_titular = "";
$nombre_metodo = "";
$numero_operacion = "";
$id_boleta = "";

$metodos_validos = ['Débito', 'Crédito', 'Transferencia', 'Efectivo', 'Webpay', 'Otro'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre_titular = trim($_POST["nombre_titular"] ?? '');
    $nombre_metodo = trim($_POST["nombre_metodo"] ?? '');
    $numero_operacion = trim($_POST["numero_operacion"] ?? '');
    $id_boleta = trim($_POST["id_boleta"] ?? '');

    if (empty($nombre_titular) || empty($nombre_metodo) || empty($id_boleta)) {
        $mensaje = "Todos los campos marcados con * son obligatorios.";
    } elseif (!in_array($nombre_metodo, $metodos_validos)) {
        $mensaje = "Método de pago inválido. Elija una opción válida.";
    } elseif (!is_numeric($id_boleta)) {
        $mensaje = "ID de boleta inválido.";
    } else {
        $query = pg_query_params($conn, "
            INSERT INTO Metodo_Pago (nombre_titular, nombre_metodo, numero_operacion, id_boleta)
            VALUES ($1, $2, $3, $4)
        ", [$nombre_titular, $nombre_metodo, $numero_operacion ?: null, $id_boleta]);

        if ($query) {
            header("Location: index.php");
            exit();
        } else {
            $mensaje = "Error al insertar el método de pago. Verifique los datos.";
        }
    }
}

$boletas = pg_query($conn, "SELECT id_boleta, monto FROM Boleta ORDER BY id_boleta");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Insertar Método de Pago</title>
    <link rel="stylesheet" href="../../../../css/CRUD/style_crud_insert.css">
</head>
<body>

<div class="form-container">
    <h2>Añadir Método de Pago</h2>

    <?php if ($mensaje): ?>
        <p class="error"><?= htmlspecialchars($mensaje) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Titular: *</label>
        <input type="text" name="nombre_titular" required value="<?= htmlspecialchars($nombre_titular) ?>">

        <label>Método de Pago: *</label>
        <select name="nombre_metodo" required>
            <option value="">Seleccione una opción</option>
            <?php foreach ($metodos_validos as $opcion): ?>
                <option value="<?= $opcion ?>" <?= $nombre_metodo === $opcion ? 'selected' : '' ?>>
                    <?= $opcion ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Número de Operación:</label>
        <input type="text" name="numero_operacion" value="<?= htmlspecialchars($numero_operacion) ?>">

        <label>Boleta Asociada: *</label>
        <select name="id_boleta" required>
            <option value="">Seleccione una boleta</option>
            <?php while ($b = pg_fetch_assoc($boletas)): ?>
                <option value="<?= $b['id_boleta'] ?>" <?= $id_boleta == $b['id_boleta'] ? 'selected' : '' ?>>
                    ID <?= $b['id_boleta'] ?> - $<?= $b['monto'] ?>
                </option>
            <?php endwhile; ?>
        </select>

        <button type="submit" class="btn">Guardar</button>
        <a href="index.php" class="btn-volver">← Cancelar</a>
    </form>
</div>

</body>
</html>
