<?php
include("../../../conexion.php");

if (!isset($_GET['id_huesped'])) {
    echo json_encode([]);
    exit;
}

$id_huesped = $_GET['id_huesped'];

$query = pg_query_params($conn, "
    SELECT r.id_reserva, r.fecha_entrada, r.fecha_salida
    FROM reserva r
    WHERE r.id_huesped = $1
      AND r.estado = 'confirmada'
      AND NOT EXISTS (
          SELECT 1 FROM opinion o WHERE o.id_reserva = r.id_reserva
      )
    ORDER BY r.fecha_entrada DESC
", [$id_huesped]);

$reservas = [];
while ($row = pg_fetch_assoc($query)) {
    $reservas[] = $row;
}

header('Content-Type: application/json');
echo json_encode($reservas);
