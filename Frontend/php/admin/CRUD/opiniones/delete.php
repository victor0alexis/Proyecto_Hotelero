<?php
include("../../../conexion.php");
session_start();

if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

$id = $_GET['id'] ?? null;

if ($id) {
    pg_query($conn, "DELETE FROM opinion WHERE id_opinion = $id");
}

header("Location: index.php");
exit();
?>
