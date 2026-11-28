<?php
    $id_usuario = $_SESSION['id_usuario'] ?? NULL;

    //Sino hay sesion iniciada
    if (!$id_usuario) {
        header("Location: ../auth/authPage.php");
        exit;
    }

    //Obtenemos de la API
    $url = "http://localhost$baseUrl/api/usuarios/$id_usuario";
    $respuesta = file_get_contents($url);
    $datos = json_decode($respuesta, true);

    if (!$datos || !$datos['success']) {
        die("No se pudo obtener la información del usuario");
    }

    $usuario = $datos['Usuarios'];
    $nombreUsuario = trim($usuario['nombre'] ?? '');
    $inicial = $nombreUsuario !== '' ? mb_strtoupper(mb_substr($nombreUsuario, 0, 1, 'UTF-8'), 'UTF-8') : '?';

    $urlFoto = $usuario['url_foto'] ?? '';
    $tieneFoto = $urlFoto !== '' && strpos($urlFoto, 'blank-profile-picture') === false;

    //ROL_PROFESOR y ROL_ADMIN se definen en index.php

    //El rol de la base de datos es el que manda (la sesion puede estar desactualizada si lo acaban de aprobar)
    $idRol = (int)($usuario['id_rol'] ?? $_SESSION['id_rol'] ?? 0);
    $_SESSION['id_rol'] = $idRol;

    $esProfesor = $idRol === ROL_PROFESOR;
    $esAdmin = $idRol === ROL_ADMIN;

    //Si es profesor, buscamos la universidad y la carrera de su solicitud aprobada
    $aplicacion = null;
    if ($esProfesor) {
        $urlAplicacion = "http://localhost$baseUrl/api/profesores?usuario=$id_usuario&estado=APROBADA&orden=DESC";
        $respuestaAplicacion = @file_get_contents($urlAplicacion);
        $datosAplicacion = $respuestaAplicacion ? json_decode($respuestaAplicacion, true) : null;

        if ($datosAplicacion && !empty($datosAplicacion['success']) && is_array($datosAplicacion['Aplicaciones'])) {
            $aplicacion = $datosAplicacion['Aplicaciones'][0] ?? null;
        }
    }
    ob_start(); 
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
        <button id="btnRegresar" onclick="regresar('<?= $baseUrl ?>/')">Regresar</button>
        <div id="panelDatosBasicos">
            <?php if ($tieneFoto): ?>
                <img class="foto-perfil"
                    src="<?= $baseUrl ?>/<?= htmlspecialchars($urlFoto) ?>"
                    alt="Foto de perfil" width="150" height="150"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="foto-inicial" style="display:none;"><?= htmlspecialchars($inicial) ?></div>
            <?php else: ?>
                <div class="foto-inicial"><?= htmlspecialchars($inicial) ?></div>
            <?php endif; ?>
            <button onclick="abrirPanelFoto()" id="btnFoto">Cambiar Foto de Perfil</button>
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
            <p id="profesor" onclick="quieroSerProfesor('<?= $baseUrl ?>')">Quiero ser un profesor<i class='bx bx-right-arrow-alt'></i></p>
        <?php endif; ?>
    </div>
            

    <!--Actualización de información-->
    <div id="panelActualizarInfo">
        <form id="formActualizarInfo">
            <h1>Actualizar información</h1><br>
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
            <p id="requisitosContrasena"></p>

            <label for="contrasenaValidar">Validar contraseña:</label>
            <div class="contenedorInputs">
                <input type="password" name="contrasenaValidar" id="contrasenaValidar" class="inputContrasena" ><i class="bx bx-show" onclick="mostrarContrasena(this)"></i>
            </div>
            <p id="errorContrasenaNueva"></p>

            <label for="nivelEstudio">Nivel de estudio:</label><br>
            <select id="nivelEstudio" name="nivelEstudio">
                <option value="">Seleccione un nivel educativo <h1>▾</h1></option>
                <option value="1">Primaria</option>
                <option value="2">Secundaria</option>
                <option value="3">Bachillerato</option>
                <option value="4">Universidad</option>
                <option value="5">Maestria</option>
            </select>
            <br>
            <button type="submit" id="btnActualizar">Actualizar Información</button>
        </form>
    </div>
    
    <div id="panelCambioFoto">
        <button id="botonSalir" type="button" onclick="cerrarPanelFoto()">
            <i class='bx bx-x'></i>
        </button>

        <form id="formFotoPerfil">

            <h2>Cambiar foto de perfil</h2>
            <p>Selecciona una imagen para actualizar tu perfil.</p>

            <label for="imagenPerfil" class="selectorImagen">
                <i class='bx bx-cloud-upload'></i>
                <span>Seleccionar imagen</span>
            </label>

            <input type="file" id="imagenPerfil" name="imagenPerfil"
                accept="image/jpeg,image/png,image/webp" required>

            <p id="nombreArchivo">Ningún archivo seleccionado</p>

            <button type="submit" id="btnSubirFoto">
                Subir imagen
            </button>
        </form>
    </div>

    <div id="opacidad" onclick="cerrarPanelFoto()"></div>
    <script>window.APP_CONFIG = { api: '<?= $baseUrl ?>/api', baseUrl: '<?= $baseUrl ?>' };</script>
    <script>
        const idUsuario = <?= json_encode($_SESSION['id_usuario']) ?>;
    </script>
    <script src="<?= $baseUrl ?>/public/js/perfilUsuario.js"></script>
    <script src="<?= $baseUrl ?>/public/js/auth.js"></script>
</body>
</html>