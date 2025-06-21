<?php
include("../../../conexion.php");
session_start();

if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'huesped') {
    header("Location: ../../../login/login.php");
    exit();
}

$id_huesped = $_SESSION['id_huesped'];
$id_opinion = (int)$_GET['id'];

// Verificar que la opinión pertenece al usuario
$result = pg_query_params($conn, "SELECT id_opinion FROM opinion WHERE id_opinion = $1 AND id_huesped = $2", array($id_opinion, $id_huesped));

if (pg_num_rows($result) === 0) {
    header("Location: index.php");
    exit();
}

// Eliminar la opinión
pg_query_params($conn, "DELETE FROM opinion WHERE id_opinion = $1 AND id_huesped = $2", array($id_opinion, $id_huesped));

header("Location: index.php");
exit();
