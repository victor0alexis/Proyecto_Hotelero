<?php 
include("../../../conexion.php");
session_start();

// Verifica que el usuario tenga permisos de administrador
if (!isset($_SESSION["username"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../../../login/login.php");
    exit();
}

// Verifica que se haya pasado el parámetro id por URL.
$id_habitacion = $_GET["eliminar"] ?? null;

if (!$id_habitacion) {
    header("Location: index.php");
    exit();
}

// Marcar la habitación como inactiva (eliminación lógica)
$desactivar = pg_query_params($conn,
    "UPDATE habitacion SET estado_actividad = 'inactivo' WHERE id_habitacion = $1",
    array($id_habitacion)
);

if ($desactivar) {
    header("Location: index.php?eliminado=1");
    exit();
} else {
    echo "Error al inhabilitar la habitación.";
}
?>
