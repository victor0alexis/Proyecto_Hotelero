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

//Si no, hay parametro "$id_usuario".
if (!$id_usuario) {
    //Redirige a "index.php".
    header("Location: index.php");
    exit();
}

// Procesar Eliminacion del Usuario.
$eliminar = pg_query_params($conn,
    "DELETE FROM usuario WHERE id_usuario = $1",
    array($id_usuario)
);

if ($eliminar) {
    header("Location: index.php?eliminado=1");
    exit();
} else {
    echo "Error al eliminar el huésped.";
}
?>
