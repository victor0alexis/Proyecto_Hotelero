<?php
// conexion.php
$conn = pg_connect("host=localhost dbname=BD_Hotel user=postgres password=12345");

if (!$conn) {
    echo "Error en la conexión.";
}

?>
