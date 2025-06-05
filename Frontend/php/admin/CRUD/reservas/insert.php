<?php
include("../../../conexion.php");
session_start();

// Verifica que el usuario es un administrador
if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

$mensaje = "";

// Obtener lista de huéspedes y habitaciones para el formulario
$huespedes = pg_query($conn, "SELECT id_huesped, nombre FROM huesped ORDER BY nombre");
$habitaciones = pg_query($conn, "SELECT id_habitacion, tipo FROM habitacion ORDER BY tipo");

// Procesar formulario
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fecha_entrada = $_POST["fecha_entrada"];
    $fecha_salida = $_POST["fecha_salida"];
    $estado = $_POST["estado"];
    $id_huesped = $_POST["id_huesped"];
    $id_habitacion = $_POST["id_habitacion"];

    if (empty($fecha_entrada) || empty($fecha_salida) || empty($id_huesped) || empty($id_habitacion)) {
        $mensaje = "Todos los campos excepto el estado son obligatorios.";
    } elseif ($fecha_entrada > $fecha_salida) {
        $mensaje = "La fecha de entrada no puede ser posterior a la fecha de salida.";
    } else {
        $insert = pg_query_params($conn, "
            INSERT INTO reserva (fecha_entrada, fecha_salida, estado, id_huesped, id_habitacion)
            VALUES ($1, $2, $3, $4, $5)", 
            array($fecha_entrada, $fecha_salida, $estado, $id_huesped, $id_habitacion)
        );

        if ($insert) {
            header("Location: index.php?mensaje=Reserva+registrada+correctamente");
            exit();
        } else {
            $mensaje = "Error al registrar la reserva.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Reserva</title>
    <link rel="stylesheet" href="../../../../css/CRUD/style_crud_insert.css">
</head>
<body>
<div class="form-container">
    <h2>Registrar Reserva</h2>

    <?php if ($mensaje): ?>
        <div class="mensaje"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="fecha_entrada">Fecha de Entrada:</label>
            <input type="date" name="fecha_entrada" required>
        </div>

        <div class="form-group">
            <label for="fecha_salida">Fecha de Salida:</label>
            <input type="date" name="fecha_salida" required>
        </div>

        <div class="form-group">
            <label for="estado">Estado:</label>
            <select name="estado" required>
                <option value="pendiente" selected>Pendiente</option>
                <option value="confirmada">Confirmada</option>
                <option value="cancelada">Cancelada</option>
                <option value="por ocupar">Por ocupar</option>
                <option value="en transcurso">En transcurso</option>
                <option value="finalizada">Finalizada</option>
            </select>
        </div>

        <div class="form-group">
            <label for="id_huesped">Huésped:</label>
            <select name="id_huesped" required>
                <option value="">Seleccione un huésped</option>
                <?php while ($h = pg_fetch_assoc($huespedes)): ?>
                    <option value="<?= $h['id_huesped'] ?>"><?= htmlspecialchars($h['nombre']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="id_habitacion">Habitación:</label>
            <select name="id_habitacion" required>
                <option value="">Seleccione una habitación</option>
                <?php while ($hab = pg_fetch_assoc($habitaciones)): ?>
                    <option value="<?= $hab['id_habitacion'] ?>">#<?= $hab['id_habitacion'] ?> - <?= htmlspecialchars($hab['tipo']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-buttons">
            <button type="submit">Registrar</button>
            <a href="index.php" class="btn-volver">Volver</a>
        </div>
    </form>
</div>
</body>
</html>
