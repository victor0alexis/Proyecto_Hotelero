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
$mensaje = "";

$consulta = pg_query_params($conn, "
    SELECT 
        si.*,
        si.tipo_servicio,
        si.id_servicio,
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
    WHERE si.id_servicio_incluido = $1
", [$id]);


$servicio = pg_fetch_assoc($consulta);
$habitaciones = pg_query($conn, "SELECT id_habitacion FROM habitacion ORDER BY id_habitacion");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descripcion = trim($_POST["descripcion"]);
    $costo = trim($_POST["costo"]);
    $personal_encargado = trim($_POST["personal_encargado"]);
    $id_habitacion = !empty($_POST['id_habitacion']) ? $_POST['id_habitacion'] : null;
    $id_reserva = !empty($_POST['id_reserva']) ? $_POST['id_reserva'] : null;

    // Validaciones
    if (empty($descripcion) || empty($costo) || empty($personal_encargado)) {
        $mensaje = "Todos los campos son obligatorios.";
    } elseif (!preg_match('/^[a-zA-ZÁÉÍÓÚÑáéíóúñ\s.,:;-]{1,200}$/u', $descripcion)) {
        $mensaje = "La descripción contiene caracteres inválidos o es muy larga.";
    } elseif (!ctype_digit($costo) || intval($costo) <= 0 || intval($costo) % 1000 !== 0) {
        $mensaje = "El costo debe ser un número entero positivo y múltiplo de 1000.";
    } elseif (!preg_match('/^[a-zA-ZÁÉÍÓÚÑáéíóúñ\s.,-]{1,200}$/u', $personal_encargado)) {
        $mensaje = "El nombre del encargado contiene caracteres inválidos o es muy largo.";
    } else {
        // Actualizar descripción y costo según el tipo
        $tabla = "";
        $campo_id = "";
        switch ($servicio['tipo_servicio']) {
            case 'transporte':
                $tabla = "servicio_transporte";
                $campo_id = "id_servicio_transporte";
                break;
            case 'lavanderia':
                $tabla = "servicio_lavanderia";
                $campo_id = "id_servicio_lavanderia";
                break;
            case 'habitacion':
                $tabla = "servicio_habitacion";
                $campo_id = "id_servicio_habitacion";
                break;
        }

        $actualizar_servicio = pg_query_params($conn, "
            UPDATE $tabla SET descripcion = $1, costo = $2 WHERE $campo_id = $3
        ", [$descripcion, $costo, $servicio['id_servicio']]);

        $actualizar_incluido = pg_query_params($conn, "
            UPDATE servicio_incluido
            SET personal_encargado = $1,
                id_habitacion = $2,
                id_reserva = $3
            WHERE id_servicio_incluido = $4
        ", [$personal_encargado, $id_habitacion, $id_reserva, $id]);

        if ($actualizar_servicio && $actualizar_incluido) {
            header("Location: index.php?mensaje=Servicio+actualizado");
            exit();
        } else {
            $mensaje = "Error al actualizar el servicio.";
        }
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
        cargarReservasPorHabitacion();
        document.getElementById("id_habitacion").addEventListener("change", cargarReservasPorHabitacion);
    });
    </script>
</head>
<body>

<div class="crud-form-container">
    <h2>Editar Servicio ID #<?= $servicio['id_servicio_incluido'] ?></h2>

    <?php if (!empty($mensaje)): ?>
        <div class="mensaje"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Tipo de Servicio:</label>
        <input type="text" value="<?= ucfirst($servicio['tipo_servicio']) ?>" disabled>

        <label>Descripción:</label>
        <input type="text" name="descripcion" required maxlength="200"
               value="<?= htmlspecialchars($_POST['descripcion'] ?? $servicio['descripcion']) ?>">

        <label>Costo:</label>
        <input type="text" name="costo" required
               value="<?= htmlspecialchars($_POST['costo'] ?? $servicio['costo']) ?>">

        <label>Encargado:</label>
        <input type="text" name="personal_encargado" required maxlength="200"
               value="<?= htmlspecialchars($_POST['personal_encargado'] ?? $servicio['personal_encargado']) ?>">

        <label>Habitación:</label>
        <select name="id_habitacion" id="id_habitacion">
            <option value="">-- Sin asignar --</option>
            <?php while ($hab = pg_fetch_assoc($habitaciones)): ?>
                <option value="<?= $hab['id_habitacion'] ?>"
                    <?= ($hab['id_habitacion'] == ($_POST['id_habitacion'] ?? $servicio['id_habitacion'])) ? 'selected' : '' ?>>
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
