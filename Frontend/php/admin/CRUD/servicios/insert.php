<?php
include("../../../conexion.php");
session_start();

if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $tipo_servicio = $_POST["tipo_servicio"];
    $descripcion = trim($_POST["descripcion"]);
    $costo = $_POST["costo"];
    $personal = trim($_POST["personal"]);

    // Validaciones
    if (empty($tipo_servicio) || empty($descripcion) || empty($costo) || empty($personal)) {
        $mensaje = "Todos los campos son obligatorios.";
    } elseif (!is_numeric($costo) || $costo < 0) {
        $mensaje = "El costo debe ser un número positivo.";
    } else {
        // Determinar tabla e ID según tipo
        $tabla_servicio = "";
        $campo_id = "";

        switch ($tipo_servicio) {
            case "transporte":
                $tabla_servicio = "servicio_transporte";
                $campo_id = "id_servicio_transporte";
                break;
            case "lavanderia":
                $tabla_servicio = "servicio_lavanderia";
                $campo_id = "id_servicio_lavanderia";
                break;
            case "habitacion":
                $tabla_servicio = "servicio_habitacion";
                $campo_id = "id_servicio_habitacion";
                break;
            default:
                $mensaje = "Tipo de servicio inválido.";
        }

        if (!$mensaje) {
            // Insertar en tabla correspondiente
            $insert_servicio = pg_query_params(
                $conn,
                "INSERT INTO $tabla_servicio (Descripcion, Costo) VALUES ($1, $2) RETURNING $campo_id AS id_servicio",
                array($descripcion, $costo)
            );

            if ($insert_servicio && $row = pg_fetch_assoc($insert_servicio)) {
                $id_servicio = $row['id_servicio'];

                // Insertar en servicio_incluido
                $insert_incluido = pg_query_params(
                    $conn,
                    "INSERT INTO servicio_incluido (id_servicio, tipo_servicio, personal_encargado)
                     VALUES ($1, $2, $3)",
                    array($id_servicio, $tipo_servicio, $personal)
                );

                if ($insert_incluido) {
                    header("Location: index.php?mensaje=Servicio+registrado+correctamente");
                    exit();
                } else {
                    $mensaje = "Error al registrar en Servicio_Incluido.";
                }
            } else {
                $mensaje = "Error al registrar el servicio específico.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Servicio</title>
    <link rel="stylesheet" href="../../../../css/CRUD/style_crud_insert.css">
</head>
<body>
<div class="form-container">
    <h2>Registrar Servicio</h2>

    <?php if ($mensaje): ?>
        <div class="mensaje"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="tipo_servicio">Tipo de Servicio:</label>
            <select name="tipo_servicio" required>
                <option value="">Seleccione</option>
                <option value="transporte">Transporte</option>
                <option value="lavanderia">Lavandería</option>
                <option value="habitacion">Habitación</option>
            </select>
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción:</label>
            <input type="text" name="descripcion" required>
        </div>

        <div class="form-group">
            <label for="costo">Costo (formato: 0.000):</label>
            <input type="text" name="costo" required pattern="^\d+(\.\d{1,3})?$" title="Ej: 123.456">
        </div>

        <div class="form-group">
            <label for="personal">Personal Encargado:</label>
            <input type="text" name="personal" required>
        </div>

        <div class="form-buttons">
            <button type="submit">Registrar</button>
            <a href="index.php" class="btn-volver">Volver</a>
        </div>
    </form>
</div>
</body>
</html>
