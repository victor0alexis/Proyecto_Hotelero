<?php
session_start();
include("../../php/conexion.php");

// Verificar si el usuario tiene rol de huésped
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'huesped') {
    header("Location: ../../php/login/login.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'] ?? null;

// Validar datos del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_habitacion = intval($_POST['id_habitacion'] ?? 0);
    $fecha_inicio = $_POST['fecha_inicio'] ?? null;
    $fecha_fin = $_POST['fecha_fin'] ?? null;
    $num_personas = intval($_POST['num_personas'] ?? 1);

    if ($id_habitacion && $fecha_inicio && $fecha_fin && $id_usuario) {
        // Verificamos si el huésped existe y obtenemos su ID
        $res = pg_query_params($conn, "SELECT id_huesped FROM huesped WHERE id_usuario = $1", [$id_usuario]);
        if ($res && pg_num_rows($res) > 0) {
            $huesped_data = pg_fetch_assoc($res);
            $id_huesped = $huesped_data['id_huesped'];

            // Insertar en la tabla Reserva
            $estado = 'pendiente';
            $consulta_insert = pg_query_params($conn, "
                INSERT INTO reserva (fecha_entrada, fecha_salida, estado, id_huesped, id_habitacion)
                VALUES ($1, $2, $3, $4, $5)
            ", [$fecha_inicio, $fecha_fin, $estado, $id_huesped, $id_habitacion]);

            if ($consulta_insert) {
                // Redirigir a una página de confirmación
                header("Location: reserva_confirmacion.php?exito=1");
                exit();
            } else {
                echo "<p>Error al registrar la reserva.</p>";
            }
        } else {
            echo "<p>No se pudo obtener el huésped.</p>";
        }
    } else {
        echo "<p>Faltan datos del formulario.</p>";
    }
} else {
    echo "<p>Acceso no permitido.</p>";
}
?>
