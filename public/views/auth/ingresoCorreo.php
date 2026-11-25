<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= $baseUrl ?>/public/css/auth.css">
    <title>Recuperar mi contraseña</title>
</head>
<body>
    <div class="contenedorRecuperar">
        <!--ELEMENTOS DECORATIVOS-->
        <div class="hojasFondoRecuperar"></div>
        <!--ELEMENTOS PRINCIPALES-->
        <div class="subContenedorRecuperar">
            <form id="formCorreoRecuperacion">
                <h1>¿Has olvidado tu contraseña?</h1>
                <p>No hay problema, ingresa tu correo y podras cambiarla</p>
                <!--correo-->
                <label for="correoRecuperacion">Correo:</label>
                <div class="contenedorInputs">
                    <input type="email" name="correoRecuperacion" id="correoRecuperacion" required><br>
                </div>
                <br>
                <button type="submit" class="btnSubmit">Enviar correo</button>
            </form>
        </div>
    </div>
    <script src="../../js/authCorreoRecuperacion.js"></script>
    <script src="../../js/auth.js"></script>
</body>
</html>