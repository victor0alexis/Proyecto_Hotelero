<?php
include("../../../conexion.php");
session_start();

// Verificar sesión como admin
if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

$mensaje = "";

// Validar ID de reserva
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id_reserva = intval($_GET['id']);

// Obtener datos actuales
$consulta = pg_query_params($conn, "SELECT * FROM reserva WHERE id_reserva = $1", array($id_reserva));
$reserva = pg_fetch_assoc($consulta);
if (!$reserva) {
    header("Location: index.php");
    exit();
}

// Listas para selects
$huespedes = pg_query($conn, "SELECT id_huesped, nombre FROM huesped ORDER BY nombre");
$habitaciones = pg_query($conn, "SELECT id_habitacion, tipo FROM habitacion WHERE estado_actividad = 'activo' ORDER BY tipo");

// Procesar actualización
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
        // Validar huésped
        $verificar_huesped = pg_query_params($conn, "SELECT 1 FROM huesped WHERE id_huesped = $1", array($id_huesped));
        if (pg_num_rows($verificar_huesped) === 0) {
            $mensaje = "El huésped seleccionado no existe.";
        } else {
            // Validar habitación activa
            $verificar_habitacion = pg_query_params($conn, "SELECT 1 FROM habitacion WHERE id_habitacion = $1 AND estado_actividad = 'activo'", array($id_habitacion));
            if (pg_num_rows($verificar_habitacion) === 0) {
                $mensaje = "La habitación seleccionada no está disponible.";
            } else {
                // Verificar solapamiento (excluyendo la reserva actual)
                $conflicto = pg_query_params($conn, "
                    SELECT 1 FROM reserva 
                    WHERE id_habitacion = $1 
                      AND id_reserva != $2
                      AND estado != 'cancelada'
                      AND fecha_entrada <= $3 
                      AND fecha_salida >= $4
                ", array($id_habitacion, $id_reserva, $fecha_salida, $fecha_entrada));

                if (pg_num_rows($conflicto) > 0) {
                    $mensaje = "Ya existe una reserva para esta habitación en las fechas seleccionadas.";
                } else {
                    // Actualizar reserva
                    $update = pg_query_params($conn, "
                        UPDATE reserva 
                        SET fecha_entrada = $1, fecha_salida = $2, estado = $3, estado_ocupacion = $4, id_huesped = $5, id_habitacion = $6
                        WHERE id_reserva = $7
                    ", array($fecha_entrada, $fecha_salida, $estado, $estado_ocupacion, $id_huesped, $id_habitacion, $id_reserva));

                    if ($update) {
                        $mensaje = "Reserva actualizada correctamente.";
                        // Recargar datos actualizados para mostrar en el formulario
                        $consulta = pg_query_params($conn, "SELECT * FROM reserva WHERE id_reserva = $1", array($id_reserva));
                        $reserva = pg_fetch_assoc($consulta);
                    } else {
                        $mensaje = "Error al actualizar la reserva.";
                    }
                }
            }
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
                foreach ($estados as $estado_opcion) {
                    $selected = ($estado_opcion === $reserva['estado']) ? 'selected' : '';
                    echo "<option value=\"$estado_opcion\" $selected>" . ucfirst($estado_opcion) . "</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label for="estado_ocupacion">Estado Ocupación:</label>
            <select name="estado_ocupacion" required>
                <?php
                $estados_ocupacion = ['reserva en espera', 'reserva en transcurso', 'reserva finalizada'];
                foreach ($estados_ocupacion as $estado_op) {
                    $selected = ($estado_op === $reserva['estado_ocupacion']) ? 'selected' : '';
                    echo "<option value=\"$estado_op\" $selected>" . ucfirst($estado_op) . "</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label for="id_huesped">Huésped:</label>
            <select name="id_huesped" required>
                <option value="">Seleccione un huésped</option>
                <?php
                pg_result_seek($huespedes, 0);
                while ($h = pg_fetch_assoc($huespedes)): ?>
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
                <?php
                pg_result_seek($habitaciones, 0);
                while ($hab = pg_fetch_assoc($habitaciones)): ?>
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
