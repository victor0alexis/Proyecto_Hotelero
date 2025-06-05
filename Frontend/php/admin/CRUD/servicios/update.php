<?php
include("../../../conexion.php");
session_start();

// Solo permitir acceso a administradores
if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

$id_servicio = $_GET['id'] ?? 0;

// Obtener el servicio
$query = pg_query($conn, "
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
    WHERE si.id_servicio_incluido = $id_servicio
");

if (pg_num_rows($query) === 0) {
    $_SESSION['error'] = "Servicio no encontrado";
    header("Location: index.php");
    exit();
}

$servicio = pg_fetch_assoc($query);
$tipo_servicio = $servicio['tipo_servicio'];

// Obtener habitaciones disponibles
$habitaciones = pg_query($conn, "SELECT id_habitacion, tipo FROM habitacion ORDER BY id_habitacion");
// Obtener reservas activas
$reservas = pg_query($conn, "SELECT id_reserva, id_huesped FROM reserva WHERE estado = 'activa'");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $personal_encargado = $_POST['personal_encargado'];
    $id_habitacion = $_POST['id_habitacion'] ?: null;
    $id_reserva = $_POST['id_reserva'] ?: null;
    
    // Actualizar según el tipo de servicio
    switch ($tipo_servicio) {
        case 'transporte':
            $descripcion = $_POST['descripcion_transporte'];
            $costo = $_POST['costo_transporte'];
            pg_query($conn, "UPDATE servicio_transporte SET descripcion = '$descripcion', costo = $costo WHERE id_servicio_transporte = {$servicio['id_servicio']}");
            break;
        case 'lavanderia':
            $descripcion = $_POST['descripcion_lavanderia'];
            $costo = $_POST['costo_lavanderia'];
            pg_query($conn, "UPDATE servicio_lavanderia SET descripcion = '$descripcion', costo = $costo WHERE id_servicio_lavanderia = {$servicio['id_servicio']}");
            break;
        case 'habitacion':
            $descripcion = $_POST['descripcion_habitacion'];
            $costo = $_POST['costo_habitacion'];
            pg_query($conn, "UPDATE servicio_habitacion SET descripcion = '$descripcion', costo = $costo WHERE id_servicio_habitacion = {$servicio['id_servicio']}");
            break;
    }
    
    // Actualizar servicio_incluido
    $update = pg_query($conn, "UPDATE servicio_incluido 
                              SET personal_encargado = '$personal_encargado', 
                                  id_habitacion = $id_habitacion, 
                                  id_reserva = $id_reserva
                              WHERE id_servicio_incluido = $id_servicio");
    
    if ($update) {
        $_SESSION['mensaje'] = "Servicio actualizado correctamente";
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['error'] = "Error al actualizar el servicio";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Servicio</title>
    <link rel="stylesheet" href="../../../../css/CRUD/style_crud_update.css">
</head>
<body>

<div class="crud-container">
    <header class="crud-header">
        <h1>Editar Servicio</h1>
        <p>Bienvenido, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
    </header>

    <main>
        <form action="update.php?id=<?= $id_servicio ?>" method="post">
            <input type="hidden" name="tipo_servicio" value="<?= $tipo_servicio ?>">
            
            <div class="form-group">
                <label for="personal_encargado">Personal Encargado:</label>
                <input type="text" id="personal_encargado" name="personal_encargado" value="<?= htmlspecialchars($servicio['personal_encargado']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="id_habitacion">Habitación (opcional):</label>
                <select id="id_habitacion" name="id_habitacion">
                    <option value="">-- Seleccione --</option>
                    <?php while ($hab = pg_fetch_assoc($habitaciones)): ?>
                        <option value="<?= $hab['id_habitacion'] ?>" <?= $hab['id_habitacion'] == $servicio['id_habitacion'] ? 'selected' : '' ?>>
                            <?= $hab['tipo'] ?> (ID: <?= $hab['id_habitacion'] ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="id_reserva">Reserva (opcional):</label>
                <select id="id_reserva" name="id_reserva">
                    <option value="">-- Seleccione --</option>
                    <?php while ($res = pg_fetch_assoc($reservas)): ?>
                        <option value="<?= $res['id_reserva'] ?>" <?= $res['id_reserva'] == $servicio['id_reserva'] ? 'selected' : '' ?>>
                            Reserva #<?= $res['id_reserva'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <?php if ($tipo_servicio === 'transporte'): ?>
                <div class="form-group">
                    <label for="descripcion_transporte">Descripción del Transporte:</label>
                    <input type="text" id="descripcion_transporte" name="descripcion_transporte" value="<?= htmlspecialchars($servicio['descripcion']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="costo_transporte">Costo:</label>
                    <input type="number" id="costo_transporte" name="costo_transporte" step="0.001" min="0" value="<?= $servicio['costo'] ?>" required>
                </div>
                
            <?php elseif ($tipo_servicio === 'lavanderia'): ?>
                <div class="form-group">
                    <label for="descripcion_lavanderia">Descripción del Servicio de Lavandería:</label>
                    <input type="text" id="descripcion_lavanderia" name="descripcion_lavanderia" value="<?= htmlspecialchars($servicio['descripcion']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="costo_lavanderia">Costo:</label>
                    <input type="number" id="costo_lavanderia" name="costo_lavanderia" step="0.001" min="0" value="<?= $servicio['costo'] ?>" required>
                </div>
                
            <?php elseif ($tipo_servicio === 'habitacion'): ?>
                <div class="form-group">
                    <label for="descripcion_habitacion">Descripción del Servicio de Habitación:</label>
                    <input type="text" id="descripcion_habitacion" name="descripcion_habitacion" value="<?= htmlspecialchars($servicio['descripcion']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="costo_habitacion">Costo:</label>
                    <input type="number" id="costo_habitacion" name="costo_habitacion" step="0.001" min="0" value="<?= $servicio['costo'] ?>" required>
                </div>
            <?php endif; ?>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-guardar">Actualizar</button>
                <a href="index.php" class="btn btn-cancelar">Cancelar</a>
            </div>
        </form>
    </main>

    <footer class="crud-footer">
        <a href="../../panel_admin.php" class="btn-volver">← Volver al panel</a>
    </footer>
</div>

</body>
</html>