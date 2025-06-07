<?php
include("../../../conexion.php");
session_start();

// Verificación de administrador
if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

// Obtener datos para selects
$habitaciones = pg_query($conn, "SELECT id_habitacion, tipo FROM habitacion ORDER BY id_habitacion");
$reservas = pg_query($conn, "SELECT id_reserva, id_huesped FROM reserva WHERE estado = 'activa'");

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_servicio = $_POST['tipo_servicio'];
    $personal_encargado = $_POST['personal_encargado'];
    $id_habitacion = !empty($_POST['id_habitacion']) ? $_POST['id_habitacion'] : null;
    $id_reserva = !empty($_POST['id_reserva']) ? $_POST['id_reserva'] : null;
    
    // Validar tipo de servicio
    if (!in_array($tipo_servicio, ['transporte', 'lavanderia', 'habitacion'])) {
        $_SESSION['error'] = "Tipo de servicio no válido";
        header("Location: index.php");
        exit();
    }

    // Insertar servicio específico
    $descripcion = $_POST['descripcion'];
    $costo = $_POST['costo'];
    
    // Validar que el costo no sea vacío y sea numérico
         if ($costo === '' || !is_numeric($costo)) {
             $_SESSION['error'] = "Por favor ingrese un costo válido.";
             header("Location: insert.php");
             exit();
    switch ($tipo_servicio) {
        case 'transporte':
            $result = pg_query_params($conn, 
                "INSERT INTO servicio_transporte (descripcion, costo) VALUES ($1, $2) RETURNING id_servicio_transporte", 
                [$descripcion, $costo]);
            break;
        case 'lavanderia':
            $result = pg_query_params($conn, 
                "INSERT INTO servicio_lavanderia (descripcion, costo) VALUES ($1, $2) RETURNING id_servicio_lavanderia", 
                [$descripcion, $costo]);
            break;
        case 'habitacion':
            $result = pg_query_params($conn, 
                "INSERT INTO servicio_habitacion (descripcion, costo) VALUES ($1, $2) RETURNING id_servicio_habitacion", 
                [$descripcion, $costo]);
            break;
    }
    
    if ($result) {
        $row = pg_fetch_assoc($result);
        $id_servicio = $row['id_servicio_transporte'] ?? $row['id_servicio_lavanderia'] ?? $row['id_servicio_habitacion'];
        
        // Insertar en servicio_incluido
        $insert = pg_query_params($conn, 
            "INSERT INTO servicio_incluido (id_servicio, tipo_servicio, personal_encargado, id_habitacion, id_reserva) 
             VALUES ($1, $2, $3, $4, $5)", 
            [$id_servicio, $tipo_servicio, $personal_encargado, $id_habitacion, $id_reserva]);
        
        if ($insert) {
            $_SESSION['mensaje'] = "Servicio agregado correctamente";
            header("Location: index.php");
            exit();
        }
    }
    
}
    $_SESSION['error'] = "Error al agregar el servicio";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Añadir Servicio</title>
    <link rel="stylesheet" href="../../../../css/CRUD/style_crud_form.css">
    <script>
        function actualizarFormulario() {
            const tipoServicio = document.getElementById('tipo_servicio').value;
            
            // Mostrar campos específicos según el tipo de servicio
            document.getElementById('campos-transporte').style.display = 
                tipoServicio === 'transporte' ? 'block' : 'none';
            document.getElementById('campos-lavanderia').style.display = 
                tipoServicio === 'lavanderia' ? 'block' : 'none';
            document.getElementById('campos-habitacion').style.display = 
                tipoServicio === 'habitacion' ? 'block' : 'none';
        }
    </script>
</head>
<body onload="actualizarFormulario()">

<div class="crud-container">
    <header class="crud-header">
        <h1>Añadir Servicio</h1>
        <p>Bienvenido, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
    </header>

    <main>
        <form action="insert.php" method="post">
            <div class="form-group">
                <label for="tipo_servicio">Tipo de Servicio:</label>
                <select id="tipo_servicio" name="tipo_servicio" onchange="actualizarFormulario()" required>
                    <option value="">-- Seleccione --</option>
                    <option value="transporte">Transporte</option>
                    <option value="lavanderia">Lavandería</option>
                    <option value="habitacion">Habitación</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="personal_encargado">Personal Encargado:</label>
                <input type="text" id="personal_encargado" name="personal_encargado" required>
            </div>
            
            <div class="form-group">
                <label for="id_habitacion">Habitación (opcional):</label>
                <select id="id_habitacion" name="id_habitacion">
                    <option value="">-- Seleccione --</option>
                    <?php while ($hab = pg_fetch_assoc($habitaciones)): ?>
                        <option value="<?= $hab['id_habitacion'] ?>">
                            <?= htmlspecialchars($hab['tipo']) ?> (ID: <?= $hab['id_habitacion'] ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="id_reserva">Reserva (opcional):</label>
                <select id="id_reserva" name="id_reserva">
                    <option value="">-- Seleccione --</option>
                    <?php while ($res = pg_fetch_assoc($reservas)): ?>
                        <option value="<?= $res['id_reserva'] ?>">
                            Reserva #<?= $res['id_reserva'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <!-- Campos específicos para cada tipo de servicio -->
            <div id="campos-transporte" style="display: none;">
                <div class="form-group">
                    <label for="descripcion">Descripción del Transporte:</label>
                    <input type="text" id="descripcion" name="descripcion">
                </div>
                
                <div class="form-group">
                    <label for="costo">Costo:</label>
                    <input type="number" id="costo" name="costo" step="0.001" min="0" required>
                </div>
            </div>
            
            <div id="campos-lavanderia" style="display: none;">
                <div class="form-group">
                    <label for="descripcion">Descripción del Servicio de Lavandería:</label>
                    <input type="text" id="descripcion" name="descripcion">
                </div>
                
                <div class="form-group">
                    <label for="costo">Costo:</label>
                    <input type="number" id="costo" name="costo" step="0.001" min="0" required>
                </div>
            </div>
            
            <div id="campos-habitacion" style="display: none;">
                <div class="form-group">
                    <label for="descripcion">Descripción del Servicio de Habitación:</label>
                    <input type="text" id="descripcion" name="descripcion">
                </div>
                
                <div class="form-group">
                    <label for="costo">Costo:</label>
                    <input type="number" id="costo" name="costo" step="0.001" min="0" required>
                </div>
            </div>
            
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