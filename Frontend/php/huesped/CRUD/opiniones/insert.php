<?php
include("../../../conexion.php");
session_start();

if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'huesped') {
    header("Location: ../../../login/login.php");
    exit();
}

$id_huesped = $_SESSION['id_huesped'];

// --- Consultar reservas activas del huésped ---
$reservas_query = pg_query_params($conn, "
    SELECT r.id_reserva, r.fecha_entrada, r.fecha_salida, h.tipo 
    FROM reserva r
    JOIN habitacion h ON r.id_habitacion = h.id_habitacion
    WHERE r.id_huesped = $1 AND r.estado = 'confirmada'
    ORDER BY r.fecha_entrada DESC
", array($id_huesped));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comentario = trim($_POST['comentario']);
    $clasificacion = (int)$_POST['clasificacion'];
    $id_reserva = (int)$_POST['id_reserva'];  // <-- capturamos id_reserva

    if ($comentario !== '' && $clasificacion >= 1 && $clasificacion <= 5 && $id_reserva > 0) {
        // Verificamos si ya existe una opinión para esta reserva
        $verificar = pg_query_params($conn, "
            SELECT 1 FROM opinion
            WHERE id_huesped = $1 AND id_reserva = $2
        ", array($id_huesped, $id_reserva));

        if (pg_num_rows($verificar) > 0) {
            $error = "Ya has dejado una opinión para esta reserva. Solo puedes opinar una vez.";
        } else {

        $result = pg_query_params($conn, "
            INSERT INTO opinion (id_huesped, id_reserva, comentario, clasificacion, fecha)
            VALUES ($1, $2, $3, $4, CURRENT_DATE)
        ", array($id_huesped, $id_reserva, $comentario, $clasificacion));

        if ($result) {
            header("Location: index.php");
            exit();
        } else {
                // Si falla por otra razón
                $error_pg = pg_last_error($conn);
                if (strpos($error_pg, 'unica_opinion_por_reserva') !== false) {
                    $error = "Ya has dejado una opinión para esta reserva. Solo puedes opinar una vez.";
                } else {
                    $error = "Error al guardar la opinión.";
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
    <link rel="stylesheet" href="../../../../css/style_opiniones.css" />
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
                        Reserva Habitación: <?= $reserva['tipo'] ?> (<?= $reserva['id_reserva'] ?>: <?= $reserva['fecha_entrada'] ?> - <?= $reserva['fecha_salida'] ?>)
                    </option>
                <?php endwhile; ?>
            </select><br><br>

            <label for="comentario">Comentario:</label><br>
            <textarea name="comentario" id="comentario" rows="5" required></textarea><br><br>

            <label for="clasificacion">Calificación (1 a 5):</label><br>
            <select name="clasificacion" id="clasificacion" required>
                <option value="">Seleccione</option>
                <?php for ($i=1; $i <= 5; $i++): ?>
                    <option value="<?= $i ?>"><?= $i ?></option>
                <?php endfor; ?>
            </select><br><br>

            <button type="submit" class="btn btn-crear">Guardar</button>
            <a href="index.php" class="btn btn-cancelar">Cancelar</a>
        </form>
    </main>

    <footer class="crud-footer"></footer>
</div>

</body>
</html>