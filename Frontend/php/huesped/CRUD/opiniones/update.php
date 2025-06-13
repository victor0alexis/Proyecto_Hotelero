<?php
include("../../../conexion.php");
session_start();

if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'huesped') {
    header("Location: ../../../login/login.php");
    exit();
}

$id_huesped = $_SESSION['id_huesped'];
$id_opinion = (int)$_GET['id'];

// Verificar que la opinión pertenece al usuario
$result = pg_query_params($conn, "SELECT * FROM opinion WHERE id_opinion = $1 AND id_huesped = $2", array($id_opinion, $id_huesped));

if (pg_num_rows($result) === 0) {
    // No existe o no pertenece a este usuario
    header("Location: index.php");
    exit();
}

$opinion = pg_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comentario = trim($_POST['comentario']);
    $clasificacion = (int)$_POST['clasificacion'];

    if ($comentario !== '' && $clasificacion >= 1 && $clasificacion <= 5) {
        $update = pg_query_params($conn, "
            UPDATE opinion SET comentario = $1, clasificacion = $2 WHERE id_opinion = $3 AND id_huesped = $4
        ", array($comentario, $clasificacion, $id_opinion, $id_huesped));

        if ($update) {
            header("Location: index.php");
            exit();
        } else {
            $error = "Error al actualizar la opinión.";
        }
    } else {
        $error = "Por favor, ingresa un comentario y una calificación válida (1-5).";
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Editar Opinión</title>
    <link rel="stylesheet" href="../../../../css/style_opiniones.css" />
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

        <form method="POST" action="update.php?id=<?= $id_opinion ?>">
            <label for="comentario">Comentario:</label><br>
            <textarea name="comentario" id="comentario" rows="5" required><?= htmlspecialchars($opinion['comentario']) ?></textarea><br><br>

            <label for="clasificacion">Calificación (1 a 5):</label><br>
            <select name="clasificacion" id="clasificacion" required>
                <option value="">Seleccione</option>
                <?php for ($i=1; $i <= 5; $i++): ?>
                    <option value="<?= $i ?>" <?= ($opinion['clasificacion'] == $i) ? 'selected' : '' ?>><?= $i ?></option>
                <?php endfor; ?>
            </select><br><br>

            <button type="submit" class="btn btn-editar">Actualizar</button>
            <a href="index.php" class="btn btn-cancelar">Cancelar</a>
        </form>
    </main>

    <footer class="crud-footer"></footer>
</div>

</body>
</html>
