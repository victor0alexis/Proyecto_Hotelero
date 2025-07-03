<?php
include("../../conexion.php");
session_start();

if (!isset($_SESSION['username'], $_SESSION['id_huesped']) || $_SESSION['rol'] !== 'huesped') {
    header("Location: ../../../login/login.php");
    exit();
}

$id_huesped = $_SESSION['id_huesped'];
$id_opinion = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verificar que la opinión pertenece al usuario
$result = pg_query_params($conn, "
    SELECT * FROM opinion WHERE id_opinion = $1 AND id_huesped = $2
", [$id_opinion, $id_huesped]);

if (!$result || pg_num_rows($result) === 0) {
    $error = "No se encontró la opinión o no tienes permiso para editarla.";
} else {
    $opinion = pg_fetch_assoc($result);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($opinion)) {
    $comentario = trim($_POST['comentario'] ?? '');
    $calificacion = (int)($_POST['calificacion'] ?? 0);

    if ($comentario !== '' && $calificacion >= 1 && $calificacion <= 5) {
        $update = pg_query_params($conn, "
            UPDATE opinion SET comentario = $1, calificacion = $2
            WHERE id_opinion = $3 AND id_huesped = $4
        ", [$comentario, $calificacion, $id_opinion, $id_huesped]);

        if ($update) {
            header("Location: index.php");
            exit();
        } else {
            $error = "Error al actualizar la opinión.";
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
    <title>Editar Opinión</title>
    <link rel="stylesheet" href="../../../css/style_opiniones.css" />
</head>
<body>

<div class="crud-container">
    <header class="crud-header">
        <h1>Editar Opinión</h1>
    </header>

    <main>
        <?php if (isset($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (isset($opinion)): ?>
        <form method="POST" action="update.php?id=<?= $id_opinion ?>">
            <label for="comentario">Comentario:</label><br>
            <textarea name="comentario" id="comentario" rows="5" required><?= htmlspecialchars($opinion['comentario']) ?></textarea><br><br>

            <label for="calificacion">Calificación (1 a 5):</label><br>
            <select name="calificacion" id="calificacion" required>
                <option value="">Seleccione</option>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <option value="<?= $i ?>" <?= ($opinion['calificacion'] == $i) ? 'selected' : '' ?>><?= $i ?></option>
                <?php endfor; ?>
            </select><br><br>

            <button type="submit" class="btn btn-editar">Actualizar</button>
            <a href="index.php" class="btn btn-cancelar">Cancelar</a>
        </form>
        <?php endif; ?>
    </main>

    <footer class="crud-footer"></footer>
</div>

</body>
</html>
