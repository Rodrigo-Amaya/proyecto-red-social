<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= $baseUrl ?>/public/css/auth.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <title>t'ASK | Iniciar sesión</title>
</head>
<body>
    <div class="contenedor">
        <!--ELEMENTOS DECORATIVOS-->
        <div class="hojasFondo"></div>
        <div class="hojasFondo2"></div>
        <!--ELEMENTOS PRINCIPALES-->
        <div class="subContenedor modo-login" id="libro"
             data-base="<?= htmlspecialchars($baseUrl) ?>"
             data-volver="<?= htmlspecialchars($volver) ?>">

            <!--LOGIN-->
            <div class="contenedorElementos" id="login">
                <form id="formLogin">
                    <h1>LogIn</h1>
                    <p>Que bueno verte de nuevo por acá</p>
                    <!--correo-->
                    <label for="correoLogin">Correo:</label>
                    <div class="contenedorInputs">
                        <input type="email" name="correoLogin" id="correoLogin" required>
                    </div>
                    <br>
                    <!--contraseña-->
                    <label for="contrasenaLogin">Contraseña:</label>
                    <div class="contenedorInputs">
                        <input type="password" name="contrasenaLogin" id="contrasenaLogin" class="inputContrasena" required><i class="bx bx-show" onclick="mostrarContrasena(this)"></i>
                    </div>
                    <br>
                    <label id="olvidoContrasena">¿Olvidaste tu contraseña?</label>
                    <br>

                    <h4 id="errorLogin"></h4>

                    <button type="submit" class="btnSubmit">Iniciar Sesión</button>
                    <p class="cambioPanel">¿No tienes cuenta? <a href="#registro" id="registrarse">Regístrate</a></p>
                </form>  
            </div>

            <!--PORTADAS -->
            <div class="contenedorElementos portada" id="portadaIzq">
                <h1 class="portadaTitulo">t'ASK</h1>
                <p>Lo que sabes hacer puede ayudar a alguien.</p>
            </div>

            <div class="contenedorElementos portada" id="portadaDer">
                <h1 class="portadaTitulo">t'ASK</h1>
                <p>Lo que sabes hacer puede ayudar a alguien.</p>
            </div>

            <!--REGISTRO-->
            <div class="contenedorElementos" id="registro">
                <form id="formRegistro">
                    <h1>Registro</h1>
                    <p>Registrate para poder iniciar a colaborar y preguntar</p>

                    <label for="nombre">Nombre:</label>
                    <div class="contenedorInputs">
                        <input type="text" name="nombre" id="nombre" required>
                    </div>
                    <br>

                    <label for="correo">Correo:</label>
                    <div class="contenedorInputs">
                        <input type="email" name="correo" id="correo" required>
                    </div>
                    <br>

                    <label for="contrasena">Contraseña:</label>
                    <div class="contenedorInputs">
                        <input type="password" name="contrasena" id="contrasena" class="inputContrasena" required><i class="bx bx-show" onclick="mostrarContrasena(this)"></i>
                    </div>
                    <br>

                    <label for="contrasenaValidar">Valide su contraseña:</label>
                    <div class="contenedorInputs">
                        <input type="password" name="contrasenaValidar" id="contrasenaValidar" class="inputContrasena" required><i class="bx bx-show" onclick="mostrarContrasena(this)"></i>
                    </div>
                    <br>

                    <label for="nivelEstudio">Nivel de estudio:</label><br>
                    <select id="nivelEstudio" name="nivelEstudio" required>
                        <option value="">Seleccione un nivel educativo</option>
                    </select>

                    <h4 id="errorRegistro"></h4><br>

                    <button type="submit" class="btnSubmit">Registrarse</button>
                    <p class="cambioPanel">¿Ya tienes cuenta? <a href="#login" id="iniciarSesion">Inicia sesión</a></p>
                </form>
            </div>
        </div>
    </div>
    <script src="<?= $baseUrl ?>/public/js/auth.js"></script>
    <script src="<?= $baseUrl ?>/public/js/authPagPrincipal.js"></script>
</body>
</html>