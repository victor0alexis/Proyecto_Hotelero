<?php 
include("../../../conexion.php");
session_start();

// Verifica que el usuario tenga permisos de administrador
if (!isset($_SESSION["username"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../../../login/login.php");
    exit();
}

// Verifica que se haya pasado el parámetro id por URL.
$id_promocion = isset($_GET["eliminar"]) ? intval($_GET["eliminar"]) : null;

if (!$id_promocion) {
    header("Location: index.php");
    exit();
}

// Eliminación física
$eliminar = pg_query_params($conn,
    "DELETE FROM promocion WHERE id_promocion = $1",
    array($id_promocion)
);

if ($eliminar) {
    header("Location: index.php?eliminado=1");
    exit();
} else {
    echo "Error al eliminar la promoción: " . pg_last_error($conn);
}
?>
