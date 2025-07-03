<?php
include("../../conexion.php");
session_start();

if (!isset($_SESSION['username'], $_SESSION['id_huesped']) || $_SESSION['rol'] !== 'huesped') {
    header("Location: ../../../login/login.php");
    exit();
}

$id_huesped = $_SESSION['id_huesped'];

// Consulta de reservas confirmadas sin opinión previa
$reservas_query = pg_query_params($conn, "
    SELECT r.id_reserva, r.fecha_entrada, r.fecha_salida, h.tipo 
    FROM reserva r
    JOIN habitacion h ON r.id_habitacion = h.id_habitacion
    LEFT JOIN opinion o ON o.id_reserva = r.id_reserva
    WHERE r.id_huesped = $1 AND r.estado = 'confirmada' AND o.id_opinion IS NULL
    ORDER BY r.fecha_entrada DESC
", [$id_huesped]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comentario = trim($_POST['comentario'] ?? '');
    $calificacion = (int)($_POST['calificacion'] ?? 0);
    $id_reserva = (int)($_POST['id_reserva'] ?? 0);

    if ($comentario !== '' && $calificacion >= 1 && $calificacion <= 5 && $id_reserva > 0) {
        // Validar que la reserva pertenezca al huésped y esté confirmada
        $validar_reserva = pg_query_params($conn, "
            SELECT 1 FROM reserva 
            WHERE id_reserva = $1 AND id_huesped = $2 AND estado = 'confirmada'
        ", [$id_reserva, $id_huesped]);

        if (pg_num_rows($validar_reserva) === 0) {
            $error = "La reserva seleccionada no es válida.";
        } else {
            // Verificamos si ya existe una opinión
            $verificar = pg_query_params($conn, "
                SELECT 1 FROM opinion WHERE id_huesped = $1 AND id_reserva = $2
            ", [$id_huesped, $id_reserva]);

            if (pg_num_rows($verificar) > 0) {
                $error = "Ya has dejado una opinión para esta reserva.";
            } else {
                $result = pg_query_params($conn, "
                    INSERT INTO opinion (id_huesped, id_reserva, comentario, calificacion, fecha)
                    VALUES ($1, $2, $3, $4, CURRENT_DATE)
                ", [$id_huesped, $id_reserva, $comentario, $calificacion]);

                if ($result) {
                    header("Location: index.php");
                    exit();
                } else {
                    $pg_error = pg_last_error($conn);
                    $error = strpos($pg_error, 'unica_opinion_por_reserva') !== false
                        ? "Ya has dejado una opinión para esta reserva."
                        : "Error al guardar la opinión.";
                }
            }
        }
    } else {
        $error = "Por favor, completa todos los campos correctamente.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Nueva Opinión</title>
    <link rel="stylesheet" href="../../../css/style_opiniones.css" />
</head>
<body>

<div class="crud-container">
    <header class="crud-header">
        <h1>Nueva Opinión</h1>
    </header>

    <main>
        <?php if (isset($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST" action="insert.php">
            <label for="id_reserva">Reserva:</label><br>
            <select name="id_reserva" id="id_reserva" required>
                <option value="">Seleccione una reserva</option>
                <?php while ($reserva = pg_fetch_assoc($reservas_query)): ?>
                    <option value="<?= $reserva['id_reserva'] ?>">
                        Habitación <?= htmlspecialchars($reserva['tipo']) ?> (<?= $reserva['fecha_entrada'] ?> - <?= $reserva['fecha_salida'] ?>)
                    </option>
                <?php endwhile; ?>
            </select><br><br>

            <label for="comentario">Comentario:</label><br>
            <textarea name="comentario" id="comentario" rows="5" required></textarea><br><br>

            <label for="calificacion">Calificación (1 a 5):</label><br>
            <select name="calificacion" id="calificacion" required>
                <option value="">Seleccione</option>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <option value="<?= $i ?>"><?= $i ?></option>
                <?php endfor; ?>
            </select><br><br>

            <button type="submit" class="btn btn-crear">Guardar</button>
            <a href="index.php" class="btn btn-cancelar">Cancelar</a>
        </form>
    </main>
</div>

</body>
</html>
