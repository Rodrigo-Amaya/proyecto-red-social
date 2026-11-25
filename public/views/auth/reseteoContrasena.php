<?php
/*$token = $_GET['token'] ?? null;
    if (!$token) {
        header('Location: /public/views/auth/authPage.php');
        exit;
    }*/
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../css/auth.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <title>Recuperar mi contraseña</title>
</head>
<body>
    <div class="contenedorRecuperar">
        <!--ELEMENTOS DECORATIVOS-->
        <div class="hojasFondoRecuperar"></div>
        <!--ELEMENTOS PRINCIPALES-->
        <div class="subContenedorRecuperar">
            <form id="formCambioContrasena">
                <h1>Cambiar contraseña</h1>
                <p>Ingresa tu nuvea contraseña</p>
                <!--contraseña-->
                <label for="contrasenaCambio">Contraseña:</label><br>
                <div class="contenedorInputs">
                    <input type="password" name="contrasenaCambio" id="contrasenaCambio" class="inputContrasena" required><i class="bx bx-show" onclick="mostrarContrasena(this)"></i>
                </div>
                <br>
                <label for="contrasenaValidarCambio">Valide su contraseña:</label><br>
                <div class="contenedorInputs">
                    <input type="password" name="contrasenaValidarCambio" id="contrasenaValidarCambio" class="inputContrasena" required><i class="bx bx-show" onclick="mostrarContrasena(this)"></i>
                </div>
                <br>

                <button type="submit" class="btnSubmit">Cambiar contraseña</button>
            </form>
        </div>
    </div>
    <script src="../../js/auth.js"></script>
    <script src="../../js/authReseteoContrasena.js"></script>
</body>
</html>