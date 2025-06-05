<?php
include("../../../conexion.php");
session_start();

// Solo permitir acceso a administradores
if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

$tipo_servicio = $_GET['tipo'] ?? '';

// Obtener habitaciones disponibles
$habitaciones = pg_query($conn, "SELECT id_habitacion, tipo FROM habitacion ORDER BY id_habitacion");
// Obtener reservas activas
$reservas = pg_query($conn, "SELECT id_reserva, id_huesped FROM reserva WHERE estado = 'activa'");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_servicio = $_POST['tipo_servicio'];
    $personal_encargado = $_POST['personal_encargado'];
    $id_habitacion = $_POST['id_habitacion'] ?: null;
    $id_reserva = $_POST['id_reserva'] ?: null;
    
    // Insertar en la tabla específica según el tipo de servicio
    switch ($tipo_servicio) {
        case 'transporte':
            $descripcion = $_POST['descripcion_transporte'];
            $costo = $_POST['costo_transporte'];
            $result = pg_query($conn, "INSERT INTO servicio_transporte (descripcion, costo) VALUES ('$descripcion', $costo) RETURNING id_servicio_transporte");
            break;
        case 'lavanderia':
            $descripcion = $_POST['descripcion_lavanderia'];
            $costo = $_POST['costo_lavanderia'];
            $result = pg_query($conn, "INSERT INTO servicio_lavanderia (descripcion, costo) VALUES ('$descripcion', $costo) RETURNING id_servicio_lavanderia");
            break;
        case 'habitacion':
            $descripcion = $_POST['descripcion_habitacion'];
            $costo = $_POST['costo_habitacion'];
            $result = pg_query($conn, "INSERT INTO servicio_habitacion (descripcion, costo) VALUES ('$descripcion', $costo) RETURNING id_servicio_habitacion");
            break;
        default:
            $_SESSION['error'] = "Tipo de servicio no válido";
            header("Location: index.php");
            exit();
    }
    
    if ($result) {
        $row = pg_fetch_assoc($result);
        $id_servicio = $row['id_servicio_transporte'] ?? $row['id_servicio_lavanderia'] ?? $row['id_servicio_habitacion'];
        
        // Insertar en servicio_incluido
        $insert = pg_query($conn, "INSERT INTO servicio_incluido (id_servicio, tipo_servicio, personal_encargado, id_habitacion, id_reserva) 
                                 VALUES ($id_servicio, '$tipo_servicio', '$personal_encargado', $id_habitacion, $id_reserva)");
        
        if ($insert) {
            $_SESSION['mensaje'] = "Servicio agregado correctamente";
            header("Location: index.php");
            exit();
        } else {
            $_SESSION['error'] = "Error al agregar el servicio";
        }
    } else {
        $_SESSION['error'] = "Error al agregar el servicio específico";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Añadir Servicio</title>
    <link rel="stylesheet" href="../../../../css/CRUD/style_crud_form.css">
</head>
<body>

<div class="crud-container">
    <header class="crud-header">
        <h1>Añadir Servicio</h1>
        <p>Bienvenido, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
    </header>

    <main>
        <form action="insert.php" method="post">
            <input type="hidden" name="tipo_servicio" value="<?= $tipo_servicio ?>">
            
            <div class="form-group">
                <label for="personal_encargado">Personal Encargado:</label>
                <input type="text" id="personal_encargado" name="personal_encargado" required>
            </div>
            
            <div class="form-group">
                <label for="id_habitacion">Habitación (opcional):</label>
                <select id="id_habitacion" name="id_habitacion">
                    <option value="">-- Seleccione --</option>
                    <?php while ($hab = pg_fetch_assoc($habitaciones)): ?>
                        <option value="<?= $hab['id_habitacion'] ?>"><?= $hab['tipo'] ?> (ID: <?= $hab['id_habitacion'] ?>)</option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="id_reserva">Reserva (opcional):</label>
                <select id="id_reserva" name="id_reserva">
                    <option value="">-- Seleccione --</option>
                    <?php while ($res = pg_fetch_assoc($reservas)): ?>
                        <option value="<?= $res['id_reserva'] ?>">Reserva #<?= $res['id_reserva'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <?php if ($tipo_servicio === 'transporte'): ?>
                <div class="form-group">
                    <label for="descripcion_transporte">Descripción del Transporte:</label>
                    <input type="text" id="descripcion_transporte" name="descripcion_transporte" required>
                </div>
                
                <div class="form-group">
                    <label for="costo_transporte">Costo:</label>
                    <input type="number" id="costo_transporte" name="costo_transporte" step="0.001" min="0" required>
                </div>
                
            <?php elseif ($tipo_servicio === 'lavanderia'): ?>
                <div class="form-group">
                    <label for="descripcion_lavanderia">Descripción del Servicio de Lavandería:</label>
                    <input type="text" id="descripcion_lavanderia" name="descripcion_lavanderia" required>
                </div>
                
                <div class="form-group">
                    <label for="costo_lavanderia">Costo:</label>
                    <input type="number" id="costo_lavanderia" name="costo_lavanderia" step="0.001" min="0" required>
                </div>
                
            <?php elseif ($tipo_servicio === 'habitacion'): ?>
                <div class="form-group">
                    <label for="descripcion_habitacion">Descripción del Servicio de Habitación:</label>
                    <input type="text" id="descripcion_habitacion" name="descripcion_habitacion" required>
                </div>
                
                <div class="form-group">
                    <label for="costo_habitacion">Costo:</label>
                    <input type="number" id="costo_habitacion" name="costo_habitacion" step="0.001" min="0" required>
                </div>
                
            <?php else: ?>
                <div class="form-group">
                    <label>Tipo de servicio no especificado</label>
                    <p>Por favor, seleccione un tipo de servicio válido</p>
                </div>
            <?php endif; ?>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-guardar">Guardar</button>
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