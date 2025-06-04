<?php
// conexion.php
$conn = pg_connect("host=localhost dbname=BD_Hotel user=postgres password=postgres123");

if (!$conn) {
    echo "Error en la conexión.";
}

?>
