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

// Obtener datos actuales
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

    if (empty($nuevo_titular) || empty($nuevo_metodo) || empty($nuevo_boleta)) {
        $mensaje = "Todos los campos marcados con * son obligatorios.";
    } elseif (!preg_match("/^[A-Za-zÁÉÍÓÚáéíóúÑñ]+$/", $nuevo_titular)) {
        $mensaje = "El nombre del titular solo debe contener letras, sin espacios ni símbolos.";
    } elseif (!in_array($nuevo_metodo, $metodos_validos)) {
        $mensaje = "Método de pago inválido.";
    } elseif (!is_numeric($nuevo_boleta)) {
        $mensaje = "ID de boleta inválido.";
    } elseif (!empty($nuevo_operacion) && (!ctype_digit($nuevo_operacion) || strlen($nuevo_operacion) !== 16)) {
        $mensaje = "Número de operación debe tener 16 dígitos.";
    } elseif ($nuevo_boleta != $id_boleta) {
        // Validar que la nueva boleta no esté ya usada
        $verificar = pg_query_params($conn, "
            SELECT 1 FROM Metodo_Pago WHERE id_boleta = $1 AND id_metodo_pago != $2
        ", [$nuevo_boleta, $id]);
        if (pg_num_rows($verificar) > 0) {
            $mensaje = "La nueva boleta ya tiene un método de pago.";
        }
    }

    if (!$mensaje) {
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
            $mensaje = "Error al actualizar.";
        }
    }

    // Persistencia
    $nombre_titular = $nuevo_titular;
    $nombre_metodo = $nuevo_metodo;
    $numero_operacion = $nuevo_operacion;
    $id_boleta = $nuevo_boleta;
}

// ✅ CORRECCIÓN AQUÍ: usamos pg_query_params con los 2 argumentos correctos
$boletas = pg_query_params($conn, "
    SELECT b.id_boleta, b.monto
    FROM Boleta b
    WHERE NOT EXISTS (
        SELECT 1 FROM Metodo_Pago m WHERE m.id_boleta = b.id_boleta AND m.id_metodo_pago != $1
    ) OR b.id_boleta = $2
    ORDER BY b.id_boleta
", [$id, $id_boleta]);
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
                    ID <?= $b['id_boleta'] ?> - $<?= number_format($b['monto'], 0, ',', '.') ?>
                </option>
            <?php endwhile; ?>
        </select>

        <button type="submit" class="btn">Actualizar</button>
        <a href="index.php" class="btn-volver">← Cancelar</a>
    </form>
</div>

</body>
</html>
