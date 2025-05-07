<?php
// conexion.php
$conn = pg_connect("host=localhost dbname=BD_Hotel user=postgres password=qhus60fl8gq");

if (!$conn) {
    echo "Error en la conexión.";
}

?>
