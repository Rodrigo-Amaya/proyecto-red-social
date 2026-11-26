<?php
    $id_usuario = $_SESSION['id_usuario'] ?? NULL;

    //Sino hay sesion iniciada
    if (!$id_usuario) {
        header("Location: ../auth/authPage.php");
        exit;
    }

    //Obtenemos de la API
    $url = "http://localhost/proyecto-red-social/api/usuarios/$id_usuario";
    $respuesta = file_get_contents($url);
    $datos = json_decode($respuesta, true);

    if (!$datos || !$datos['success']) {
        die("No se pudo obtener la información del usuario");
    }

    $usuario = $datos['Usuarios'];

    //ROL_PROFESOR y ROL_ADMIN se definen en index.php

    //El rol de la base de datos es el que manda (la sesion puede estar desactualizada si lo acaban de aprobar)
    $idRol = (int)($usuario['id_rol'] ?? $_SESSION['id_rol'] ?? 0);
    $_SESSION['id_rol'] = $idRol;

    $esProfesor = $idRol === ROL_PROFESOR;
    $esAdmin = $idRol === ROL_ADMIN;

    //Si es profesor, buscamos la universidad y la carrera de su solicitud aprobada
    $aplicacion = null;
    if ($esProfesor) {
        $urlAplicacion = "http://localhost/proyecto-red-social/api/profesores?usuario=$id_usuario&estado=APROBADA&orden=DESC";
        $respuestaAplicacion = @file_get_contents($urlAplicacion);
        $datosAplicacion = $respuestaAplicacion ? json_decode($respuestaAplicacion, true) : null;

        if ($datosAplicacion && !empty($datosAplicacion['success']) && is_array($datosAplicacion['Aplicaciones'])) {
            $aplicacion = $datosAplicacion['Aplicaciones'][0] ?? null;
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= $baseUrl ?>/public/css/perfilUsuario.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <title>Perfil</title>
</head>
<body>
    <!--Datos basicos-->
    <div id="datosBasicos">
        <button onclick="regresar('<?= $baseUrl ?>/')">Regresar</button>
        <div id="panelDatosBasicos">
            <img src="<?= $baseUrl ?>/<?= htmlspecialchars($usuario['url_foto'] ?? 'documentos/usuarios/imagenes/blank-profile-picture.png');?>" alt="Foto de perfil" width="150px">
            <button onclick="abrirPanelFoto()">Cambiar Foto de Perfil</button>
            <h2><?= htmlspecialchars($usuario['nombre']) ?></h2>
            <p><?= htmlspecialchars($usuario['nivel']) ?></p>
            <p id="rol"><?= htmlspecialchars($usuario['rol']) ?></p>
            <?php if ($esProfesor && $aplicacion): ?>
                <div id="datosProfesor">
                    <p><span>Universidad</span><?= htmlspecialchars($aplicacion['universidad']) ?></p>
                    <p><span>Carrera</span><?= htmlspecialchars($aplicacion['carrera']) ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php if ($esAdmin): ?>
            <div class="contenedorBoton">
                <button type="button" id="botonSolicitudes" onclick="location.href='index.php?page=solicitudes'">Solicitudes</button>
                <button type="button" id="botonGestionUsuarios" onclick="location.href='index.php?page=gestion-usuarios'">Gestionar usuarios</button>
            </div>
        <?php endif; ?>
        <?php if (!$esProfesor && !$esAdmin): ?>
            <p id="profesor" onclick="quieroSerProfesor('<?= $baseUrl ?>')">Quiero ser un profesor</p>
        <?php endif; ?>
    </div>
            

    <!--Actualización de información-->
    <div id="panelActualizarInfo">
        <form id="formActualizarInfo">
            <label for="nombreActualizar">Nombre:</label>
            <div class="contenedorInputs">
                <input type="text" name="nombreActualizar" id="nombreActualizar">
            </div>
            <label for="correoActualizar">Correo:</label>
            <div class="contenedorInputs">
                <input type="email" name="correoActualizar" id="correoActualizar" >
            </div>
            <label for="contrasenaActual">Contraseña:</label>
            <div class="contenedorInputs">
                <input type="password" name="contrasenaActual" id="contrasenaActual" class="inputContrasena" ><i class="bx bx-show" onclick="mostrarContrasena(this)"></i>
            </div>
            <label for="contrasenaNueva">Nueva contraseña:</label>
            <div class="contenedorInputs">
                <input type="password" name="contrasenaNueva" id="contrasenaNueva" class="inputContrasena" ><i class="bx bx-show" onclick="mostrarContrasena(this)"></i>
            </div>
            <label for="contrasenaValidar">Validar contraseña:</label>
            <div class="contenedorInputs">
                <input type="password" name="contrasenaValidar" id="contrasenaValidar" class="inputContrasena" ><i class="bx bx-show" onclick="mostrarContrasena(this)"></i>
            </div>
            <label for="nivelEstudio">Nivel de estudio:</label><br>
            <select id="nivelEstudio" name="nivelEstudio">
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
            <button type="submit">Actualizar Infromación</button>
        </form>
    </div>
    
    <div id="panelCambioFoto">
        <button id="botonSalir" onclick="cerrarPanelFoto()">X</button>
        <form id="formFotoPerfil">
            <h2 for="imagenPerfil">Selecciona un archivo:</h2>
            <input type="file" id="imagenPerfil" name="imagenPerfil" required>
            <button type="submit">Subir Imagen</button>
        </form>
    </div>
    <div id="opacidad"></div>
    <script>
        const idUsuario = <?= json_encode($_SESSION['id_usuario']) ?>;
    </script>
    <script src="<?= $baseUrl ?>/public/js/perfilUsuario.js"></script>
    <script src="<?= $baseUrl ?>/public/js/auth.js"></script>
</body>
</html>