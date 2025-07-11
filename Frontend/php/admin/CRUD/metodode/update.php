<?php
include("../../../conexion.php");
session_start();

if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

$id = $_GET["id"] ?? null;
if (!$id) {
    header("Location: index.php");
    exit();
}

// Obtener datos actuales del método de pago
$query = pg_query_params($conn, "SELECT * FROM Metodo_Pago WHERE id_metodo_pago = $1", [$id]);
$actual = pg_fetch_assoc($query);

if (!$actual) {
    echo "Método de pago no encontrado.";
    exit();
}

$mensaje = "";
$nombre_titular = $actual['nombre_titular'];
$nombre_metodo = $actual['nombre_metodo'];
$numero_operacion = $actual['numero_operacion'];
$id_boleta = $actual['id_boleta'];

$metodos_validos = ['Débito', 'Crédito', 'Transferencia', 'Efectivo', 'Webpay', 'Otro'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nuevo_titular = trim($_POST["nombre_titular"] ?? '');
    $nuevo_metodo = trim($_POST["nombre_metodo"] ?? '');
    $nuevo_operacion = trim($_POST["numero_operacion"] ?? '');
    $nuevo_boleta = trim($_POST["id_boleta"] ?? '');

    // Validación de campos vacíos
    if (empty($nuevo_titular) || empty($nuevo_metodo) || empty($nuevo_boleta)) {
        $mensaje = "Todos los campos marcados con * son obligatorios.";
    } elseif (!in_array($nuevo_metodo, $metodos_validos)) {
        $mensaje = "Método de pago inválido. Elija una opción válida.";
    } elseif (!is_numeric($nuevo_boleta)) {
        $mensaje = "ID de boleta inválido.";
    } else {
        // Verificar si hay cambios
        if (
            $nuevo_titular === $actual['nombre_titular'] &&
            $nuevo_metodo === $actual['nombre_metodo'] &&
            $nuevo_operacion === ($actual['numero_operacion'] ?? '') &&
            intval($nuevo_boleta) === intval($actual['id_boleta'])
        ) {
            $mensaje = "No se han realizado cambios.";
        } else {
            // Actualizar
            $update = pg_query_params($conn, "
                UPDATE Metodo_Pago
                SET nombre_titular = $1,
                    nombre_metodo = $2,
                    numero_operacion = $3,
                    id_boleta = $4
                WHERE id_metodo_pago = $5
            ", [$nuevo_titular, $nuevo_metodo, $nuevo_operacion ?: null, $nuevo_boleta, $id]);

            if ($update) {
                header("Location: index.php");
                exit();
            } else {
                $mensaje = "Error al actualizar el método de pago.";
            }
        }
    }

    // Persistir en caso de error
    $nombre_titular = $nuevo_titular;
    $nombre_metodo = $nuevo_metodo;
    $numero_operacion = $nuevo_operacion;
    $id_boleta = $nuevo_boleta;
}

$boletas = pg_query($conn, "SELECT id_boleta, monto FROM Boleta ORDER BY id_boleta");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Método de Pago</title>
    <link rel="stylesheet" href="../../../../css/CRUD/style_crud_update.css">
</head>
<body>

<div class="form-container">
    <h2>Editar Método de Pago</h2>

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
                <option value="<?= $b['id_boleta'] ?>" <?= $b['id_boleta'] == $id_boleta ? 'selected' : '' ?>>
                    ID <?= $b['id_boleta'] ?> - $<?= $b['monto'] ?>
                </option>
            <?php endwhile; ?>
        </select>

        <button type="submit" class="btn">Actualizar</button>
        <a href="index.php" class="btn-volver">← Cancelar</a>
    </form>
</div>

</body>
</html>
