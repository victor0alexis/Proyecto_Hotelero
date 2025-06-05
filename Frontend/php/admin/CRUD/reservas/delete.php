<?php
include("../../../conexion.php");
session_start();

// Verifica que el usuario tenga permisos de administrador
if (!isset($_SESSION["username"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../../../login/login.php");
    exit();
}

// Verifica que se haya pasado el parámetro id por URL.
$id_reserva = $_GET["id"] ?? null;

// Si no hay parámetro "id", redirige.
if (!$id_reserva) {
    header("Location: index.php");
    exit();
}

// Procesar eliminación de la reserva
$eliminar = pg_query_params($conn,
    "DELETE FROM reserva WHERE id_reserva = $1",
    array($id_reserva)
);

if ($eliminar) {
    header("Location: index.php?eliminado=1");
    exit();
} else {
    echo "Error al eliminar la reserva.";
}
?>
