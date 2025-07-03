<?php
include("../../conexion.php");
session_start();

// Validar autenticación
if (!isset($_SESSION['username'], $_SESSION['id_huesped']) || $_SESSION['rol'] !== 'huesped') {
    header("Location: ../login/login.php");
    exit();
}

$id_huesped = $_SESSION['id_huesped'];
$id_opinion = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Validar ID válido
if ($id_opinion <= 0) {
    header("Location: index.php?error=opinion_invalida");
    exit();
}

// Verificar propiedad de la opinión
$result = pg_query_params($conn, "
    SELECT id_opinion FROM opinion WHERE id_opinion = $1 AND id_huesped = $2
", [$id_opinion, $id_huesped]);

if (!$result || pg_num_rows($result) === 0) {
    header("Location: index.php?error=no_autorizado");
    exit();
}

// Ejecutar eliminación
$delete = pg_query_params($conn, "
    DELETE FROM opinion WHERE id_opinion = $1 AND id_huesped = $2
", [$id_opinion, $id_huesped]);

if ($delete) {
    header("Location: index.php?msg=eliminado");
} else {
    header("Location: index.php?error=fallo_eliminar");
}
exit();
