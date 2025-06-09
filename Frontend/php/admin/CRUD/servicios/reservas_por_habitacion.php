<?php
include("../../../conexion.php");

if (!isset($_GET['id_habitacion'])) {
    echo json_encode([]);
    exit();
}

$id_habitacion = $_GET['id_habitacion'];
$result = pg_query($conn, "SELECT id_reserva FROM reserva WHERE id_habitacion = '$id_habitacion' ORDER BY id_reserva");

$reservas = [];
while ($row = pg_fetch_assoc($result)) {
    $reservas[] = $row;
}

header('Content-Type: application/json');
echo json_encode($reservas);
