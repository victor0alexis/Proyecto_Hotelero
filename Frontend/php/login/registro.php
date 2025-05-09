<?php
include("../conexion.php");      //conexion BD.
session_start();                // Iniciar seccion para manejar variables "$_SESSION".

$mensaje = "";                     // Variable para mostrar errores al usuario.
$mensaje_exito = "";              // Variable para mensajes exitosos (registro correcto).

// Si se recibe ?rol=huesped o ?rol=admin, se guarda el valor. Si no, queda null.
$rol = isset($_GET['rol']) ? $_GET['rol'] : null;

if (!empty($_POST["btnregistrar"])) {      // Si se envió el formulario
    if (
        empty($_POST["nombre"]) ||
        empty($_POST["email"]) ||
        empty($_POST["clave"])
    ) {
        $mensaje = "Error: Todos los campos son obligatorios.";
    } else {
        $nombre = $_POST["nombre"];        // Obtiene el nombre ingresado.
        $email = $_POST["email"];         // Obtiene el email ingresado.
        $clave = $_POST["clave"];        // Obtiene la clave ingresado. 
        $rol = $_POST["rol"];           // Obtiene el rol oculto en el formulario.

        //verificar si el nombre esta registrado en la Base de Datos
        $check_user = pg_query_params($conn, "SELECT 1 FROM usuario WHERE username = $1", array($nombre));
        if (pg_num_rows($check_user) > 0) {

            //checkear si el usuario esta registrado.
            $mensaje = "Error: El nombre de usuario ya está registrado.";   

            // Valida que el email tenga formato correcto.             
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mensaje = "Error: La dirección de correo no es válida.";                  
        } else {

            //INSERT en tabla Usuario.
            $insert_usuario = pg_query_params($conn,
                "INSERT INTO usuario (username, clave, rol) VALUES ($1, md5($2), $3) RETURNING id_usuario",
                array($nombre, $clave, $rol)
            );
            
            //Obtener ID_USUARIO de Usuario.
            if ($insert_usuario && $row = pg_fetch_assoc($insert_usuario)) {
                $id_usuario = $row["id_usuario"];
                //Generar codigo aleatorio.
                $codigo = rand(100000, 999999);

                //INSERT en tabla Usuario.
                if ($rol === 'huesped') {
                    pg_query_params($conn,
                        "INSERT INTO huesped (id_usuario, nombre, email, telefono, codigo_verificacion) VALUES ($1, $2, $3, $4, $5)",
                        array($id_usuario, $nombre, $email, $_POST["telefono"], $codigo)
                    );
                //INSERT en tabla Administrador.
                } elseif ($rol === 'admin') {
                    pg_query_params($conn,
                        "INSERT INTO administrador (id_usuario, nombre, email, codigo_verificacion) VALUES ($1, $2, $3, $4)",
                        array($id_usuario, $nombre, $email, $codigo)
                    );
                }

                // Envíamos el correo con código de verificación.
                include("mail/enviar_codigo.php");
                enviarCodigoVerificacion($email, $codigo);

                //Registro añadido a la BD, a la espera de  la validacion del codigo.
                $mensaje_exito = "¡Registro exitoso! Redirigiendo a verificación de correo...";
                echo "<script>
                        setTimeout(function() {
                            window.location.href = 'mail/verificar.php?rol=$rol&email=$email';
                        }, 5000);
                    </script>";
            } else {
                $mensaje = "Error: No se pudo registrar el usuario.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro</title>
    <link rel="stylesheet" href="../../css/style_login.css">
</head>
<body>
<section class="login-section">
    <div class="formulario animate">

        <?php if (!$rol): ?>
            <h1>¿Qué tipo de cuenta deseas crear?</h1>
            <div style="display: flex; gap: 20px; justify-content: center;">
                <a href="registro.php?rol=huesped" class="btn-login">Registrarse como Huésped</a>
                <a href="registro.php?rol=admin" class="btn-login">Registrarse como Administrador</a>
            </div>
        <?php else: ?>
            <h1>Registro de <?= ucfirst($rol) ?></h1>

            <?php if (!empty($mensaje)): ?>
                <div class="alert"><?= $mensaje ?></div>
            <?php elseif (!empty($mensaje_exito)): ?>
                <div class="alert" style="background-color: #d1e7dd; color: #0f5132;"><?= $mensaje_exito ?></div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="rol" value="<?= $rol ?>">

                <div class="input-group">
                    <input type="text" name="nombre" pattern=".*\S.*" required>
                    <label>Nombre de Usuario</label>
                </div>

                <div class="input-group">
                    <input type="text" name="email" pattern=".*\S.*" required>
                    <label>Email</label>
                </div>

                <?php if ($rol === "huesped"): ?>
                    <div class="input-group">
                        <input type="text" name="telefono" pattern=".*\S.*" required>
                        <label>Teléfono</label>
                    </div>
                <?php endif; ?>

                <div class="input-group">
                    <input type="password" name="clave" pattern=".*\S.*" required>
                    <label>Contraseña</label>
                </div>

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
