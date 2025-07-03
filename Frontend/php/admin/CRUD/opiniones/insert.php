<?php
include("../../../conexion.php");
session_start();

if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

// Obtener huéspedes para el selector
$huespedes = pg_query($conn, "SELECT id_huesped, nombre FROM huesped ORDER BY nombre ASC");


// Procesar envío del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_huesped = $_POST['id_huesped'];
    $id_reserva = $_POST['id_reserva'];
    $comentario = $_POST['comentario'];
    $calificacion = $_POST['calificacion'];
    $fecha = date('Y-m-d'); // Solo fecha

    $query = pg_query_params($conn, "
        INSERT INTO opinion (id_huesped, id_reserva, comentario, calificacion, fecha)
        VALUES ($1, $2, $3, $4, $5)
    ", array($id_huesped, $id_reserva, $comentario, $calificacion, $fecha));

    if ($query) {
        header("Location: index.php");
        exit();
    } else {
        echo "Error al insertar opinión.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Opinión</title>
    <link rel="stylesheet" href="../../../../css/CRUD/style_crud_insert.css">
</head>
<body>

<div class="form-container">
<h2>Registrar Opinión</h2>
<form method="post">
<label for="id_huesped">Huésped:</label>
<select name="id_huesped" id="id_huesped" required>
    <option value="">Selecciona un huésped</option>
    <?php while ($h = pg_fetch_assoc($huespedes)): ?>
        <option value="<?= $h['id_huesped'] ?>"><?= htmlspecialchars($h['nombre']) ?></option>
    <?php endwhile; ?>
</select>

<label for="id_reserva">Reserva:</label>
<select name="id_reserva" id="id_reserva" required>
    <option value="">Selecciona una reserva</option>
</select>


    <label for="comentario">Comentario:</label>
    <textarea name="comentario" required></textarea>

    <label for="calificacion">Calificación (1 a 5):</label>
    <input type="number" name="calificacion" min="1" max="5" required>

    <div class="form-buttons">
        <button type="submit">Guardar</button>
        <a href="index.php" class="btn-volver">Volver</a>
    </div>
</form>
</div>

<script>
document.getElementById('id_huesped').addEventListener('change', function () {
    const idHuesped = this.value;
    const reservaSelect = document.getElementById('id_reserva');

    reservaSelect.innerHTML = '<option value="">Cargando reservas...</option>';

    fetch(`get_reservas_sin_opinion.php?id_huesped=${idHuesped}`)
        .then(response => response.json())
        .then(data => {
            reservaSelect.innerHTML = '<option value="">Selecciona una reserva</option>';
            if (data.length === 0) {
                reservaSelect.innerHTML += '<option value="">(Sin reservas disponibles)</option>';
            } else {
                data.forEach(reserva => {
                    const option = document.createElement('option');
                    option.value = reserva.id_reserva;
                    option.textContent = `#${reserva.id_reserva} (${reserva.fecha_entrada} a ${reserva.fecha_salida})`;
                    reservaSelect.appendChild(option);
                });
            }
        });
});
</script>

</body>
</html>
