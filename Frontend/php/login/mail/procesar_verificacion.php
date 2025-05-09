<?php
include("../../conexion.php");

$rol = $_POST['rol'];
$email = $_POST['email'];
$codigo = $_POST['codigo'];

if ($rol === 'huesped') {
    $query = "UPDATE huesped SET verificado = TRUE WHERE email = $1 AND codigo_verificacion = $2";
} else {
    $query = "UPDATE administrador SET verificado = TRUE WHERE email = $1 AND codigo_verificacion = $2";
}

$resultado = pg_query_params($conn, $query, array($email, $codigo));

if (pg_affected_rows($resultado) > 0) {
    header("Location: ../login.php?verificado=1");
    exit();
} else {
    header("Location: verificar.php?rol=$rol&email=$email&error=1");
    exit();
}

