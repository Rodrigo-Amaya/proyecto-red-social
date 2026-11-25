<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../css/auth.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <title>Registro</title>
</head>
<body>
    <div class="contenedor">
        <!--ELEMENTOS DECORATIVOS-->
        <div class="hojasFondo"></div>
        <div class="hojasFondo2"></div>
        <!--ELEMENTOS PRINCIPALES-->
        <div class="subContenedor">

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
                    <label for="contrasena">Contraseña:</label>
                    <div class="contenedorInputs">
                        <input type="password" name="contrasenaLogin" id="contrasenaLogin" class="inputContrasena" required><i class="bx bx-show" onclick="mostrarContrasena(this)"></i>
                    </div>
                    <br>
                    <label id="olvidoContrasena">¿Olvidaste tu contraseña?</label>
                    <br>

                    <h4 id="errorLogin"></h4>

                    <button type="submit" class="btnSubmit">Iniciar Sesión</button>
                </form>  
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
                        <option value="">Seleccione un nivel educativo <h1>▾</h1></option>
                        <option value="1">Educacion Basica</option>
                        <option value="2">Bachillerato General</option>
                        <option value="3">Bachillerato Tecnico</option>
                        <option value="4">Tecnico Superior</option>
                        <option value="5">Educacion Universitaria</option>
                        <option value="6">Egresado Universitario</option>
                        <option value="7">Licenciatura</option>
                        <option value="8">Ingenieria</option>
                        <option value="9">Maestria</option>
                        <option value="10">Doctorado</option>
                        <option value="11">Postdoctorado</option>
                        <option value="12">Profesional</option>
                    </select>

                    <h4 id="errorRegistro"></h4><br>

                    <button type="submit" class="btnSubmit">Registrarse</button>
                </form>
            </div>
        </div>
    </div>
    <script src="../../js/auth.js"></script>
    <script src="../../js/authPagPrincipal.js"></script>
</body>
</html>