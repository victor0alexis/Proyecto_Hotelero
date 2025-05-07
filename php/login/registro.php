<?php
include("../conexion.php");     // Incluir conexion
session_start();               // inicio de seccion

$mensaje = "";               // guarder posibles mensajes de error o confirmacion


$rol = isset($_GET['rol']) ? $_GET['rol'] : null; // Obtener rol desde la URL, si no se envia "null"

// Procesar formulario de registro
if (!empty($_POST["btnregistrar"])) {       //si se envio el formulario
    if (
        empty($_POST["nombre"]) ||
        empty($_POST["email"]) ||        //verificacion de campos
        empty($_POST["clave"])
    ) {
        $mensaje = "Error: Todos los campos son obligatorios.";
    } else {
        //se asignan los valores ingresados en variables PHP
        $nombre = $_POST["nombre"];
        $email = $_POST["email"];               
        $clave = $_POST["clave"];
        $rol = $_POST["rol"];  // desde campo oculto

        // Insertar en tabla usuario
        $insert_usuario = pg_query_params($conn,
            "INSERT INTO usuario (username, clave, rol) VALUES ($1, md5($2), $3) RETURNING id_usuario",
            array($nombre, $clave, $rol)
        );
        //Si la inserción fue exitosa, se extrae el id_usuario retornado y se guarda en una variable.
        if ($insert_usuario && $row = pg_fetch_assoc($insert_usuario)) {
            $id_usuario = $row["id_usuario"];
            
            //Si el usuario es huésped, se inserta su información adicional en la tabla huesped, usando el id_usuario como clave foránea.
            if ($rol === 'huesped') {
                pg_query_params($conn,
                    "INSERT INTO huesped (id_usuario, nombre, email, telefono) VALUES ($1, $2, $3, $4)",
                    array($id_usuario, $nombre, $email, $_POST["telefono"])
                );
            //Si el usuario es admin, se inserta en la tabla administrador.
            } elseif ($rol === 'admin') {
                pg_query_params($conn,
                    "INSERT INTO administrador (id_usuario, nombre, email) VALUES ($1, $2, $3)",
                    array($id_usuario, $nombre, $email)
                );
            }

            //Después del registro, redirige al usuario a la página de login
            header("Location: login.php");
            exit();
        } else {
            $mensaje = "Error: No se pudo registrar el usuario.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<!-------HEAD---------->

<head>
<meta charset="UTF-8">
<title>Registro</title>
<link rel="stylesheet" href="../../css/style_login.css">
</head>

<!-------BODY---------->

<body>

<!-------FORMULARIO REGISTRO---------->

<section class="login-section">

    <div class="formulario animate">

    <!-------Tipo de Registro que se mostrara segun "rol"---------->
    <?php if (!$rol): ?>
        <h1>¿Qué tipo de cuenta deseas crear?</h1>
        <div style="display: flex; gap: 20px; justify-content: center;">
            <a href="registro.php?rol=huesped" class="btn-login">Registrarse como Huésped</a>
            <a href="registro.php?rol=admin" class="btn-login">Registrarse como Administrador</a>
        </div>
    <?php else: ?>

        <!-------TituloRegistro---------->
        <h1>Registro de <?= ucfirst($rol) ?></h1>

    <?php if (!empty($mensaje)): ?>
        <div class="alert"><?= $mensaje ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="rol" value="<?= $rol ?>">

    <!-------username---------->
    <div class="input-group">
        <input type="text" name="nombre" required>
        <label>Nombre de Usuario</label>
    </div>

    <!-------email---------->
    <div class="input-group">
        <input type="text" name="email" required>
        <label>Email</label>
    </div>

    <!-------si es "Huesped", campo para telefono---------->
    <?php if ($rol === "huesped"): ?>
        <div class="input-group">
            <input type="text" name="telefono" required>
            <label>Teléfono</label>
        </div>
    <?php endif; ?>

    <!-------password---------->
    <div class="input-group">
        <input type="password" name="clave" required>
        <label>Contraseña</label>
    </div>

    <!-------boton registrado---------->
    <input name="btnregistrar" type="submit" value="Registrarse">
        <div class="registrarse">
        <a href="registro.php">Volver</a>
        </div>

    </form>
    <?php endif; ?>
</div>

</section>

</body>

</html>
