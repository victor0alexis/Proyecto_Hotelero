<?php
include("../../../conexion.php");
session_start();

// Verifica que el usuario tenga permisos de administrador
if (!isset($_SESSION["username"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../../../login/login.php");
    exit();
}

// Verifica que se haya pasado el parámetro id por URL.
$id_usuario = $_GET["eliminar"] ?? null;

if (!$id_usuario) {
    header("Location: index.php");
    exit();
}

// Procesar eliminación del usuario (elimina también al administrador por ON DELETE CASCADE)
$eliminar = pg_query_params($conn,
    "DELETE FROM usuario WHERE id_usuario = $1",
    array($id_usuario)
);

if ($eliminar) {
    header("Location: index.php?eliminado=1");
    exit();
} else {
    echo "Error al eliminar el administrador.";
}
?>
