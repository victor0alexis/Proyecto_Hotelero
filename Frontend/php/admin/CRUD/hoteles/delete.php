<?php
include("../../../conexion.php");
session_start();

if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

// Obtener y validar ID del hotel
$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    echo "ID de hotel inválido.";
    exit();
}

// Eliminar hotel
$result = pg_query_params($conn, "DELETE FROM hotel WHERE id_hotel = $1", array($id));

if ($result) {
    header("Location: index.php");
    exit();
} else {
    echo "Error al eliminar el hotel.";
}
?>
