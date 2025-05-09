<?php
$rol = $_GET['rol'];
$email = $_GET['email'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificar correo</title>
    <link rel="stylesheet" href="../../../css/style_login.css">

</head>
<body>


                <!-------Formulario de Verificacion del Codigo---------->
<section class="login-section">

    <div class="formulario animate">
        <?php if (isset($_GET['error']) && $_GET['error'] == 1): ?>
            <div class="alert">Código incorrecto. Inténtalo de nuevo.</div>
        <?php endif; ?>

        <h1>Verificación de correo</h1>

        <form method="POST" action="procesar_verificacion.php">
            <input type="hidden" name="rol" value="<?= htmlspecialchars($rol) ?>">
            <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">

            <div class="input-group">
                <input type="text" name="codigo" id="codigo" placeholder=" " required>
                <label for="codigo">Introduce el código enviado a tu correo</label>
            </div>
            <input type="submit" value="Verificar">
        </form>
    </div>

</section>


</body>
</html>
