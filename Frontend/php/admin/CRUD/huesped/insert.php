<?php
include("../../../conexion.php");         //conexion BD.
session_start();                         //Iniciamos session.


// Verifica que el usuario es un administrador
if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../login/login.php");
    exit();
}

//Variable para mensajes de error o éxito.
$mensaje = "";

//Obtenemos variables del formulario.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $clave = $_POST["clave"];
    $nombre = trim($_POST["nombre"]);
    $email = trim($_POST["email"]);
    $telefono = trim($_POST["telefono"]);

    //En el caso de que un campo este vacio.
    if (empty($username) || empty($clave) || empty($nombre)) {
        $mensaje = "Los campos 'Usuario', 'Contraseña' y 'Nombre' son obligatorios.";
    //restriccion email.
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "El correo electrónico no tiene un formato válido.";
    //restriccion telefono.
    } elseif (!empty($telefono) && !preg_match('/^\d{7,10}$/', $telefono)) {
        $mensaje = "El número de teléfono debe contener solo dígitos y tener entre 7 y 10 caracteres.";
    } else {
        //En el caso, de que el "$username", este registrado.
        $verificar = pg_query_params($conn, "SELECT * FROM usuario WHERE username = $1", array($username));

        //Username ya registrado.
        if (pg_num_rows($verificar) > 0) {
            $mensaje = "El nombre de usuario ya está en uso.";
        } else {
            $rol = 'huesped';                              //creamos rol.
            $clave_hash = md5($clave);            //encriptamos clave.
            //Insertamos datos en tabla "usuario".
            $insert_usuario = pg_query_params($conn, "INSERT INTO usuario (username, clave, rol) VALUES ($1, $2, $3) RETURNING id_usuario", array($username, $clave_hash, $rol));

            //si el usuario se registra correctamente, se crea un registro en huesped.
            if ($insert_usuario && $row = pg_fetch_assoc($insert_usuario)) {
                $id_usuario = $row['id_usuario'];                          //recuperamos id.
                $codigo_verificacion = rand(100000, 999999);    //codigo aleatorio.

                //insertamos datos en tabla "huesped".
                $insert_huesped = pg_query_params($conn, "INSERT INTO huesped (id_usuario, nombre, email, telefono, verificado, codigo_verificacion) VALUES ($1, $2, $3, $4, false, $5)", array($id_usuario, $nombre, $email, $telefono, $codigo_verificacion));

                //insercion exitosa.
                if ($insert_huesped) {
                    header("Location: index.php?mensaje=Huésped+registrado+correctamente");
                    exit();
                //insercion fallida.
                } else {
                    $mensaje = "Error al registrar en la tabla huésped.";
                }
            } else {
                $mensaje = "Error al registrar usuario.";
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Huésped</title>
    <link rel="stylesheet" href="../../../../css/CRUD/style_crud_insert.css">
</head>
<body>
<div class="form-container">
    <h2>Registrar Huésped</h2>

    <?php if ($mensaje): ?>
        <div class="mensaje"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="username">Usuario:</label>
            <input type="text" name="username" required>
        </div>

        <div class="form-group">
            <label for="clave">Contraseña:</label>
            <input type="password" name="clave" required>
        </div>

        <div class="form-group">
            <label for="nombre">Nombre Completo:</label>
            <input type="text" name="nombre" required>
        </div>

        <div class="form-group">
            <label for="email">Correo Electrónico:</label>
            <input type="email" name="email">
        </div>

        <div class="form-group">
            <label for="telefono">Teléfono:</label>
            <input type="text" name="telefono">
        </div>

        <div class="form-buttons">
            <button type="submit">Registrar</button>
            <a href="index.php" class="btn-volver">Volver</a>
        </div>
    </form>
</div>
</body>
</html>
