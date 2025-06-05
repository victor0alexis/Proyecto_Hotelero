<?php
include("../../../conexion.php");
session_start();

// Solo permitir acceso a administradores
if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

if (isset($_GET['eliminar'])) {
    $id_servicio = $_GET['eliminar'];
    
    // Primero obtener el tipo de servicio y el id específico
    $query = pg_query($conn, "SELECT id_servicio, tipo_servicio FROM servicio_incluido WHERE id_servicio_incluido = $id_servicio");
    
    if (pg_num_rows($query) > 0) {
        $servicio = pg_fetch_assoc($query);
        $tipo_servicio = $servicio['tipo_servicio'];
        $id_servicio_especifico = $servicio['id_servicio'];
        
        // Eliminar de servicio_incluido (esto activará ON DELETE CASCADE en las relaciones)
        $delete = pg_query($conn, "DELETE FROM servicio_incluido WHERE id_servicio_incluido = $id_servicio");
        
        if ($delete) {
            // Eliminar el servicio específico
            switch ($tipo_servicio) {
                case 'transporte':
                    pg_query($conn, "DELETE FROM servicio_transporte WHERE id_servicio_transporte = $id_servicio_especifico");
                    break;
                case 'lavanderia':
                    pg_query($conn, "DELETE FROM servicio_lavanderia WHERE id_servicio_lavanderia = $id_servicio_especifico");
                    break;
                case 'habitacion':
                    pg_query($conn, "DELETE FROM servicio_habitacion WHERE id_servicio_habitacion = $id_servicio_especifico");
                    break;
            }
            
            $_SESSION['mensaje'] = "Servicio eliminado correctamente";
        } else {
            $_SESSION['error'] = "Error al eliminar el servicio";
        }
    } else {
        $_SESSION['error'] = "Servicio no encontrado";
    }
}

header("Location: index.php");
exit();
?>