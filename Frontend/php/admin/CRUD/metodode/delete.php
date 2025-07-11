<?php
include("../../../conexion.php");
session_start();

if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

$id = $_GET['eliminar'] ?? null;
if ($id) {
    pg_query_params($conn, "DELETE FROM Metodo_Pago WHERE id_metodo_pago = $1", [$id]);
}

header("Location: index.php");
exit();
