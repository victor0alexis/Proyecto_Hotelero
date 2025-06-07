<?php
include("../../../conexion.php");
session_start();

// Verificar si hay sesión iniciada como administrador
if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

$mensaje = "";

// Verifica que se haya pasado el parámetro id por URL.
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id_reserva = intval($_GET['id']);

// Obtener datos actuales de la reserva
$consulta = pg_query_params($conn, "
    SELECT * FROM reserva WHERE id_reserva = $1
", array($id_reserva));

$reserva = pg_fetch_assoc($consulta);

// Si no se encuentra la reserva, redirigir
if (!$reserva) {
    header("Location: index.php");
    exit();
}

// Obtener lista de huéspedes y habitaciones para mostrar en el formulario
$huespedes = pg_query($conn, "SELECT id_huesped, nombre FROM huesped ORDER BY nombre");
$habitaciones = pg_query($conn, "SELECT id_habitacion, tipo FROM habitacion ORDER BY tipo");

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha_entrada = $_POST["fecha_entrada"];
    $fecha_salida = $_POST["fecha_salida"];
    $estado = $_POST["estado"];
    $estado_ocupacion = $_POST["estado_ocupacion"];
    $id_huesped = $_POST["id_huesped"];
    $id_habitacion = $_POST["id_habitacion"];

    if (empty($fecha_entrada) || empty($fecha_salida) || empty($id_huesped) || empty($id_habitacion)) {
        $mensaje = "Todos los campos excepto el estado son obligatorios.";
    } elseif ($fecha_entrada > $fecha_salida) {
        $mensaje = "La fecha de entrada no puede ser posterior a la de salida.";
    } else {
        $update = pg_query_params($conn, "
            UPDATE reserva 
            SET fecha_entrada = $1, fecha_salida = $2, estado = $3, estado_ocupacion=$4, id_huesped = $5, id_habitacion = $6 
            WHERE id_reserva = $7
        ", array($fecha_entrada, $fecha_salida, $estado, $estado_ocupacion, $id_huesped, $id_habitacion, $id_reserva));

        if ($update) {
            $mensaje = "Reserva actualizada correctamente.";
        } else {
            $mensaje = "Error al actualizar la reserva.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Reserva</title>
    <link rel="stylesheet" href="../../../../css/CRUD/style_crud_update.css">
</head>
<body>
<div class="form-container">
    <h2>Editar Reserva</h2>

    <?php if (!empty($mensaje)): ?>
        <div class="mensaje"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="fecha_entrada">Fecha de Entrada:</label>
            <input type="date" name="fecha_entrada" value="<?= htmlspecialchars($reserva['fecha_entrada']) ?>" required>
        </div>

        <div class="form-group">
            <label for="fecha_salida">Fecha de Salida:</label>
            <input type="date" name="fecha_salida" value="<?= htmlspecialchars($reserva['fecha_salida']) ?>" required>
        </div>

        <div class="form-group">
            <label for="estado">Estado:</label>
            <select name="estado" required>
                <?php
                $estados = ["pendiente", "confirmada", "cancelada"];
                foreach ($estados as $estado) {
                    $selected = ($estado === $reserva['estado']) ? 'selected' : '';
                    echo "<option value=\"$estado\" $selected>" . ucfirst($estado) . "</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label for="estado_ocupacion">Estado Ocupación:</label>
            <select name="estado_ocupacion" required> <!-- ← CORRECTO -->
                <?php
                $estados_ocupacion = ['reserva en espera', 'reserva en transcurso', 'reserva finalizada'];
                foreach ($estados_ocupacion as $estado_ocupacion) {
                    $selected = ($estado_ocupacion === $reserva['estado_ocupacion']) ? 'selected' : '';
                    echo "<option value=\"$estado_ocupacion\" $selected>" . ucfirst($estado_ocupacion) . "</option>";
                }
                ?>
            </select>
        </div>


        <div class="form-group">
            <label for="id_huesped">Huésped:</label>
            <select name="id_huesped" required>
                <option value="">Seleccione un huésped</option>
                <?php while ($h = pg_fetch_assoc($huespedes)): ?>
                    <option value="<?= $h['id_huesped'] ?>" <?= $h['id_huesped'] == $reserva['id_huesped'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($h['nombre']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="id_habitacion">Habitación:</label>
            <select name="id_habitacion" required>
                <option value="">Seleccione una habitación</option>
                <?php while ($hab = pg_fetch_assoc($habitaciones)): ?>
                    <option value="<?= $hab['id_habitacion'] ?>" <?= $hab['id_habitacion'] == $reserva['id_habitacion'] ? 'selected' : '' ?>>
                        #<?= $hab['id_habitacion'] ?> - <?= htmlspecialchars($hab['tipo']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-buttons">
            <button type="submit">Guardar Cambios</button>
            <a href="index.php" class="btn-volver">Volver</a>
        </div>
    </form>
</div>
</body>
</html>
