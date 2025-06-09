<?php
session_start();
include("../../php/conexion.php");

// Verificar si hay sesión y es huésped
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'huesped') {
    $url_actual = $_SERVER['REQUEST_URI'];
    header("Location: ../../php/login/login.php?redirect=" . urlencode($url_actual));
    exit();
}

$id_usuario = $_SESSION['id_usuario'] ?? null;

if (!$id_usuario) {
    echo "<p>Sesión inválida.</p>";
    exit();
}

$errores = "";
$exito = false;

// Si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_habitacion = intval($_POST['id_habitacion'] ?? 0);
    $fecha_inicio = $_POST['fecha_inicio'] ?? null;
    $fecha_fin = $_POST['fecha_fin'] ?? null;
    $num_personas = intval($_POST['num_personas'] ?? 1);
    $hoy = date('Y-m-d');

    if ($id_habitacion && $fecha_inicio && $fecha_fin && $id_usuario) {
        if ($fecha_inicio < $hoy || $fecha_fin <= $fecha_inicio) {
            $errores = "Fechas inválidas.";
        } else {
            // Validar capacidad y disponibilidad
            $hab_data = pg_query_params($conn, "SELECT capacidad FROM habitacion WHERE id_habitacion = $1", [$id_habitacion]);
            if (pg_num_rows($hab_data) === 0) {
                $errores = "La habitación no existe.";
            } else {
                $hab_info = pg_fetch_assoc($hab_data);
                if ($num_personas > $hab_info['capacidad']) {
                    $errores = "Número de personas supera la capacidad.";
                } else {
                    $disp = pg_query_params($conn, "
                        SELECT 1 FROM reserva 
                        WHERE id_habitacion = $1 
                        AND estado IN ('pendiente', 'confirmada')
                        AND (
                            ($2 BETWEEN fecha_entrada AND fecha_salida) OR
                            ($3 BETWEEN fecha_entrada AND fecha_salida) OR
                            (fecha_entrada BETWEEN $2 AND $3)
                        )
                    ", [$id_habitacion, $fecha_inicio, $fecha_fin]);

                    if (pg_num_rows($disp) > 0) {
                        $errores = "No hay disponibilidad en esas fechas.";
                    } else {
                        // Obtener ID huésped
                        $res = pg_query_params($conn, "SELECT id_huesped FROM huesped WHERE id_usuario = $1", [$id_usuario]);
                        if ($res && pg_num_rows($res) > 0) {
                            $id_huesped = pg_fetch_result($res, 0, 0);
                            $estado = 'pendiente';
                            $result_insert = pg_query_params($conn, "
                                INSERT INTO reserva (fecha_entrada, fecha_salida, estado, id_huesped, id_habitacion)
                                VALUES ($1, $2, $3, $4, $5) RETURNING id_reserva
                            ", [$fecha_inicio, $fecha_fin, $estado, $id_huesped, $id_habitacion]);

                            if ($result_insert && pg_num_rows($result_insert) > 0) {
                                $new_id = pg_fetch_result($result_insert, 0, 'id_reserva');
                                header("Location: reserva_confirmacion.php?id=$new_id&exito=1");
                                exit();
                            } else {
                                $errores = "Error al guardar la reserva.";
                            }


                        } else {
                            $errores = "No se pudo verificar al huésped.";
                        }
                    }
                }
            }
        }
    } else {
        $errores = "Todos los campos son obligatorios.";
    }
} else {
    // Si se accede con GET, obtener el ID de la habitación
    if (!isset($_GET['id'])) {
        header("Location: ../habitacion/habitaciones.php");
        exit();
    }
    $id_habitacion = intval($_GET['id']);
}

// Obtener datos habitación y huésped para mostrar en el formulario
$habitacion = pg_fetch_assoc(pg_query_params($conn, "
    SELECT ID_Habitacion, Tipo, Precio, Descripcion, Imagen, Capacidad
    FROM Habitacion WHERE ID_Habitacion = $1
", [$id_habitacion]));

$huesped = pg_fetch_assoc(pg_query_params($conn, "
    SELECT nombre, email FROM huesped WHERE id_usuario = $1
", [$id_usuario]));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reservar Habitación</title>
    <link rel="stylesheet" href="../../css/Reserva/style_reserva_formulario.css">
</head>
<body>

<div class="reserva-container">
    <h1>Reserva de Habitación</h1>

    <?php if ($exito): ?>
        <div class="mensaje-exito">
            <p>✅ ¡Reserva registrada exitosamente!</p>
            <a href="../reservas_hechas.php" class="btn">Ver mis reservas</a>
            <a href="../habitaciones.php" class="btn">Volver a habitaciones</a>
        </div>
    <?php else: ?>

        <?php if ($errores): ?>
            <div class="mensaje-error"><p>⚠ <?= htmlspecialchars($errores) ?></p></div>
        <?php endif; ?>

        <div class="habitacion-info">
            <img src="../../img/habitaciones/<?= htmlspecialchars($habitacion['imagen']) ?>" alt="Imagen habitación">
            <div>
                <h2><?= htmlspecialchars($habitacion['tipo']) ?></h2>
                <p><strong>Precio:</strong> $<?= number_format($habitacion['precio'], 3) ?></p>
                <p><strong>Capacidad:</strong> <?= $habitacion['capacidad'] ?> personas</p>
                <p><?= htmlspecialchars($habitacion['descripcion']) ?></p>
            </div>
        </div>

        <div class="huesped-info">
            <h3>Datos del Huésped</h3>
            <p><strong>Nombre:</strong> <?= htmlspecialchars($huesped['nombre']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($huesped['email']) ?></p>
        </div>

        <form action="" method="POST" class="formulario-reserva">
            <input type="hidden" name="id_habitacion" value="<?= $habitacion['id_habitacion'] ?>">

            <label for="fecha_inicio">Fecha de Entrada:</label>
            <input type="date" name="fecha_inicio" required min="<?= date('Y-m-d') ?>">

            <label for="fecha_fin">Fecha de Salida:</label>
            <input type="date" name="fecha_fin" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>">

            <label for="num_personas">Número de Personas:</label>
            <input type="number" name="num_personas" min="1" max="<?= $habitacion['capacidad'] ?>" value="1" required>

            <button type="submit">Confirmar Reserva</button>
        </form>

        <a href="../habitacion/detalle_habitacion.php?id=<?= $habitacion['id_habitacion'] ?>" class="btn-volver">← Volver</a>
    <?php endif; ?>
</div>

</body>
</html>
