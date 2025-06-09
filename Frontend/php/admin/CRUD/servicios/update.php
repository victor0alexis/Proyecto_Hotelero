<?php
include("../../../conexion.php");
session_start();

if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];

$consulta = pg_query($conn, "
    SELECT 
        si.*,
        CASE 
            WHEN si.tipo_servicio = 'transporte' THEN st.descripcion
            WHEN si.tipo_servicio = 'lavanderia' THEN sl.descripcion
            WHEN si.tipo_servicio = 'habitacion' THEN sh.descripcion
        END AS descripcion,
        CASE 
            WHEN si.tipo_servicio = 'transporte' THEN st.costo
            WHEN si.tipo_servicio = 'lavanderia' THEN sl.costo
            WHEN si.tipo_servicio = 'habitacion' THEN sh.costo
        END AS costo
    FROM servicio_incluido si
    LEFT JOIN servicio_transporte st ON si.id_servicio = st.id_servicio_transporte AND si.tipo_servicio = 'transporte'
    LEFT JOIN servicio_lavanderia sl ON si.id_servicio = sl.id_servicio_lavanderia AND si.tipo_servicio = 'lavanderia'
    LEFT JOIN servicio_habitacion sh ON si.id_servicio = sh.id_servicio_habitacion AND si.tipo_servicio = 'habitacion'
    WHERE si.id_servicio_incluido = $id
");

$servicio = pg_fetch_assoc($consulta);
$habitaciones = pg_query($conn, "SELECT id_habitacion FROM habitacion ORDER BY id_habitacion");

// Guardar cambios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $personal_encargado = $_POST['personal_encargado'];
    $id_habitacion = !empty($_POST['id_habitacion']) ? $_POST['id_habitacion'] : 'NULL';
    $id_reserva = !empty($_POST['id_reserva']) ? $_POST['id_reserva'] : 'NULL';

    $update_query = pg_query($conn, "
        UPDATE servicio_incluido
        SET personal_encargado = '$personal_encargado',
            id_habitacion = $id_habitacion,
            id_reserva = $id_reserva
        WHERE id_servicio_incluido = $id
    ");

    if ($update_query) {
        header("Location: index.php");
        exit();
    } else {
        echo "Error al actualizar.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Servicio</title>
    <link rel="stylesheet" href="../../../../css/CRUD/style_crud_insert.css">
    <script>
    function cargarReservasPorHabitacion() {
        const habitacionId = document.getElementById("id_habitacion").value;
        const reservaSelect = document.getElementById("id_reserva");

        // Limpiar opciones actuales
        reservaSelect.innerHTML = '<option value="">-- Sin asignar --</option>';

        if (habitacionId !== "") {
            fetch("reservas_por_habitacion.php?id_habitacion=" + habitacionId)
                .then(res => res.json())
                .then(data => {
                    data.forEach(reserva => {
                        const option = document.createElement("option");
                        option.value = reserva.id_reserva;
                        option.text = reserva.id_reserva;
                        if (reserva.id_reserva == <?= json_encode($servicio['id_reserva']) ?>) {
                            option.selected = true;
                        }
                        reservaSelect.appendChild(option);
                    });
                });
        }
    }

    window.addEventListener("DOMContentLoaded", () => {
        cargarReservasPorHabitacion(); // inicial al cargar
        document.getElementById("id_habitacion").addEventListener("change", cargarReservasPorHabitacion);
    });
    </script>
</head>
<body>

<div class="crud-form-container">
    <h2>Editar Servicio ID #<?= $servicio['id_servicio_incluido'] ?></h2>

    <form method="POST">
        <label>Tipo de Servicio:</label>
        <select disabled>
            <option selected><?= ucfirst($servicio['tipo_servicio']) ?></option>
        </select>

        <label>Descripción:</label>
        <input type="text" value="<?= htmlspecialchars($servicio['descripcion']) ?>" readonly>

        <label>Costo:</label>
        <input type="text" value="<?= number_format($servicio['costo'], 3) ?>" readonly>

        <label>Encargado:</label>
        <input type="text" name="personal_encargado" value="<?= htmlspecialchars($servicio['personal_encargado']) ?>" required>

        <label>Habitación:</label>
        <select name="id_habitacion" id="id_habitacion">
            <option value="">-- Sin asignar --</option>
            <?php while ($hab = pg_fetch_assoc($habitaciones)): ?>
                <option value="<?= $hab['id_habitacion'] ?>" <?= $servicio['id_habitacion'] == $hab['id_habitacion'] ? 'selected' : '' ?>>
                    <?= $hab['id_habitacion'] ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Reserva:</label>
        <select name="id_reserva" id="id_reserva">
            <option value="">-- Sin asignar --</option>
        </select>

        <div class="form-actions">
            <button type="submit" class="btn-guardar">Guardar Cambios</button>
            <a href="index.php" class="btn-cancelar">Cancelar</a>
        </div>
    </form>
</div>

</body>
</html>
