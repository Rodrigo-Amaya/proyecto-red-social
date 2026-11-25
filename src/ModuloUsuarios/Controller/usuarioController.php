<?php

    class UsuarioController implements Controlador
        {
            public function __construct(
                private Usuario $modelo
            ) {}

            //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //========== CONTROL DE METODOS ==========
            public function control(string $metodo, array $partes): void
            {
                $parte = $partes[1] ?? null;

                // url: /usuarios
                if ($parte === null) {
                    match ($metodo) {
                        'GET'  => $this->buscarUsuario(),
                        'POST' => $this->ingresarNuevoUsuario(),
                        default => noPermitido(),
                    };
                    return;
                }

                if($parte === "reseteoContrasena"){
                    match ($metodo){
                        'POST'    => $this->resetearContrasena(),
                        default  => noPermitido(),
                    };
                    return;
                }

                // url: usuarios/{id}
                $idUsuario = validarId($parte, 'ID inválido');

                if (isset($partes[2])) {
                    if($partes[2] === "cambiarFoto"){
                        match ($metodo){
                            'POST'    => $this->cambiarFotoPerfil($idUsuario),
                            default  => noPermitido(),
                        };
                        return;
                    }
                    noEncontrado();
                }

                match ($metodo) {
                    'GET'    => $this->obtenerUsuario($idUsuario),
                    'PUT'    => $this->actualizarusuario($idUsuario),
                    'DELETE' => $this->eliminarUsuario($idUsuario),
                    'PATCH'  => $this->cambiarRolUsuario($idUsuario),
                    default  => noPermitido(),
                };
            }

            //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //========== GET ==========

            //busqueda de usuario por id
            function obtenerUsuario(int $id): void{
                $resultado = $this->modelo->buscarPorID($id);

                //Sino hay resultado
                if (!$resultado) {
                    respuesta(404, ['success' => false,'mensaje' => 'Usuario no encontrado o bloqueado']);
                }

                respuesta(200, [
                    'success' => true,
                    'Usuarios' => $resultado
                ]);
            }


            //Busqueda de usuario
            function buscarUsuario(): void{
                if(isset($_GET["busqueda"])||
                isset($_GET["correo"])||
                isset($_GET["nivelEstudio"])||
                isset($_GET["orden"]) ||
                isset($_GET["bloqueado"])){
                    $busqueda = trim($_GET["busqueda"] ?? '');
                    $correo = trim($_GET["correo"] ?? '');
                    $nivel = trim($_GET["nivelEstudio"] ?? '');
                    $orden = trim($_GET["orden"] ?? '');
                    $orden = strtoupper($orden);
                    $bloqueado = trim($_GET["bloqueado"] ?? '');

                    //Validamos si algun campo se ingresado esta vacio
                    validarCamposVacios([$busqueda, $correo, $nivel, $orden, $bloqueado]);

                    //Validamos si en nivel de estudio se ingreso un numero entero
                    if($nivel){
                        validarCampoEntero($nivel);
                    }
                    
                    //Validamos si correo esta como un correo
                    if($correo){
                        if(!filter_var($correo, FILTER_VALIDATE_EMAIL)){
                            respuesta(400, ['success' => false, 'Mensaje' => 'Formato de correo incorrecto']);
                        }
                    }

                    //Validamos si en nivel es alguno valido
                    if($orden){
                        if($orden !== 'ASC' && $orden !== 'DESC'){
                            respuesta(400, ['success' => false, 'Mensaje' => 'Tipo de orden inválido']);
                            exit;
                        }
                    }

                    //Validamos si se pide un valor correcto para bloqueado
                    if($bloqueado){
                        validarCampoEntero($bloqueado);
                        validarTrueOFalse($bloqueado);
                    }

                    $resultado = $this->modelo->buscarPorFiltros(
                        $busqueda,
                        $correo,
                        $nivel,
                        $orden,
                        $bloqueado
                    );
                }

                //Listar todos los usuarios
                else{
                    $resultado = $this->modelo->listarUsuarios();
                }
                
                //Mensaje recibido
                if($resultado){
                    respuesta(200, ['success' => true, 'Usuarios' => $resultado]);
                }
                else{
                    respuesta(200, ['success' => true, 'Usuarios' => '']);
                }
            }

            //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //========== POST ==========
            //Generacion de token de reseteo
            function resetearContrasena(): void{
                if (isset($_POST["correoRecuperacion"])) {

                    $correo = trim($_POST["correoRecuperacion"]);

                    if ($correo === '') {
                        respuesta(400, ['success' => false, 'Mensaje' => 'Campo sin llenar de correo']);
                    }

                    $resultado = $this->modelo->recuperarContrasena(
                        $correo
                    );

                    if($resultado === 1){
                        respuesta(409, ['success' => false, 'Mensaje' => 'Correo incorrecto o bloqueado']);
                    }
                    else{
                        respuesta(200, ['success' => true, 'Mensaje' => 'Se ha enviado un correo de recuperación']);
                    }
                }
            }

             function ingresarNuevoUsuario(): void {

                $nombre = trim($_POST["nombre"] ?? '');
                $correo = trim($_POST["correo"] ?? '');
                $contrasena = $_POST["contrasena"] ?? '';
                $contrasenaValidar = $_POST["contrasenaValidar"] ?? '';
                $nivel = trim($_POST["nivelEstudio"] ?? '');

                //Si alguno de los campos ingresados esta vacio
                if ( $nombre === '' || $correo === '' || $contrasena === '' || $contrasenaValidar === ''|| $nivel === '') {
                    respuesta(400, ['success' => false, 'mensaje' => 'Todos los campos son obligatorios']);
                }

                //Longitudes de las columnas
                if (mb_strlen($nombre, 'UTF-8') > 100) {
                    respuesta(400, ['success' => false, 'mensaje' => 'El nombre es muy largo']);
                }
                if (mb_strlen($correo, 'UTF-8') > 150) {
                    respuesta(400, ['success' => false, 'mensaje' => 'El correo es muy largo']);
                }

                //Si correo no tiene el formato correcto
                if(!filter_var($correo, FILTER_VALIDATE_EMAIL)){
                    respuesta(400, ['success' => false, 'mensaje' => 'Formato de correo incorrecto']);
                }

                //Si la contraseña tiene al menos 8 caracteres, un caracter especial, numero y mayuscula
                validarContrasena($contrasena);

                //si las contraseñas no coinciden
                if($contrasena !== $contrasenaValidar){
                    respuesta(400, ['success' => false, 'mensaje' => 'Las contraseñas no coinciden']);
                }

                //Que el nivel sea un entero
                $nivel = validarCampoEntero($nivel);

                //Valores por defecto
                date_default_timezone_set('America/El_Salvador');
                $fecha = date('Y-m-d H:i:s');

                $resultado = $this->modelo->agregarUsuario(
                    $nombre,
                    $correo,
                    $contrasena,
                    $fecha,
                    $nivel
                );

                //Cada codigo del modelo tiene su caso; los desconocidos responden error
                switch ($resultado) {
                    case 0:
                        respuesta(201, ['success' => true, 'mensaje' => 'Usuario ingresado correctamente']);
                        break;
                    case 1:
                        respuesta(409, ['success' => false, 'mensaje' => 'El correo se encuentra en uso']);
                        break;
                    case 2:
                        respuesta(400, ['success' => false, 'mensaje' => 'Nivel de estudio inválido']);
                        break;
                    case 3:
                        respuesta(500, ['success' => false, 'mensaje' => 'No se pudo asignar el rol por defecto']);
                        break;
                    default:
                        respuesta(500, ['success' => false, 'mensaje' => 'Error interno del servidor']);
                }
            }

            function cambiarFotoPerfil(int $usuario): void{
                $imagen = $_FILES["imagen"] ?? NULL;

                //Si alguno de los campos ingresados esta vacio
                if ( $usuario === '' || $imagen === NULL ) {
                    respuesta(400, ['success' => false, 'Mensaje' => 'Todos los campos son abligatorios']);
                }

                //Que el usuario no sea texto
                validarCampoEntero($usuario);
                $extension = strtolower(validarDocumento($imagen));
                if (!in_array($extension, ["jpg", "jpeg", "png", "webp"], true)) {
                    respuesta(400, ['success' => false, 'Mensaje' => 'Tipo de archivo invalido']);
                }

                $resultado = $this->modelo->cambiarImagenPerfil(
                    $usuario,
                    $imagen
                );
                
                if($resultado === 0){
                    respuesta(201, ['success' => true, 'Mensaje' => 'Se Cambio la foto de perfil con exito']);
                }
                else if($resultado === 1){
                    respuesta(400, ['success' => false, 'Mensaje' => 'Usuario no encontrado o bloqueado']);
                }
                else {
                    respuesta(500, ['success' => false, 'Mensaje' => 'Error al guardar la imagen']);
                }
            }

            //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //========== PUT ==========

            //cambio de contraseña
            function cambiarContrasenaRecuperacion(): void {
                $contrasena = $_POST["contrasenaNueva"] ?? '';
                $contrasenaValidar = $_POST["contrasenaNuevaValidar"] ?? '';
                $token = $_GET["token"];
            }

            //Agregar psoteriormente actualización por medio se session no por ID
            function actualizarusuario(int $id): void {
                $datos = leerJson();

                $nombre = $datos['nombre'] ?? null;
                $correo = $datos['correo'] ?? null;
                $contrasenaAnterior = $datos['contrasenaAnterior'] ?? null;
                $contrasenaNueva = $datos["contrasenaNueva"] ?? null;
                $contrasenaNuevaValidar = $datos["contrasenaNuevaValidar"] ?? null;
                $nivel = $datos["nivelEstudio"] ?? null;

                //Validaciones de campos
                validarCamposVacios([$nombre, $correo, $contrasenaAnterior, $contrasenaNueva, $contrasenaNuevaValidar, $nivel]);

                if($contrasenaAnterior && !$contrasenaNueva){
                    respuesta(400, ['success' => false, 'Mensaje' => 'Ingrese la nueva contraseña']);
                }

                //si las contraseñas no coinciden
                if($contrasenaNueva){
                    validarContrasena($contrasenaNueva);
                    if($contrasenaNueva !== $contrasenaNuevaValidar){
                        respuesta(400, ['success' => false, 'Mensaje' => 'Nuevas contraseñas no coinciden']);
                    }
                }

                //Si correo no tiene el formato correcto
                if($correo){
                    if(!filter_var($correo, FILTER_VALIDATE_EMAIL)){
                        respuesta(400, ['success' => false, 'Mensaje' => 'Formato de correo incorrecto']);
                    }
                }

                $resultado = $this->modelo->actualizarUsuario(
                    $id,
                    $nombre,
                    $correo,
                    $contrasenaAnterior,
                    $contrasenaNueva,
                    $nivel,
                );

                //Resultado recibido
                switch($resultado){
                    case 1:
                        respuesta(404, ['success' => false, 'Mensaje' => 'Usuario no encontrado o bloqueado']);
                        break;
                    case 2:
                        respuesta(404, ['success' => false, 'Mensaje' => 'Contraseña incorrecta']);
                        break;
                    case 3:
                        respuesta(404, ['success' => false, 'Mensaje' => 'Correo ya registrado por otro usuario']);
                        break;
                    case 4:
                        respuesta(404, ['success' => false, 'Mensaje' => 'Nivel de estudio inválido']);
                        break;
                    default:
                        respuesta(200, ['success' => true, 'Mensaje' => 'Su información se actualizó correctamente']);
                }
            }

            //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //========== DELETE ==========
            function eliminarUsuario(int $id): void {
                $resultado = $this->modelo->eliminarUsuario(
                    $id
                );

                //Resultado recibido
                if($resultado === false){
                    respuesta(404, ['success' => false,'Mensaje' => 'Usuario no encontrado o ya bloqueado'
                    ]);
                }
                else{
                    respuesta(200, ['success' => true, 'Mensaje:' => 'Usuario eliminado correctamente']);
                }
            }

            //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //========== PATCH ==========
            function cambiarRolUsuario(int $id): void {
                $datos = leerJson();

                $rol = $datos['rol'] ?? '';

                validarCamposVacios([$rol]);
                validarCampoEntero($rol);

                $resultado = $this->modelo->cambiarRolUsuario(
                    $id,
                    $rol
                );

                //Resultado recibido
                if($resultado === 1){
                    respuesta(404, ['success' => false,'Mensaje' => 'Usuario no encontrado o bloqueado'
                    ]);
                }
                else if($resultado === 2){
                    respuesta(400, ['success' => false,'Mensaje' => 'Rol inválido'
                    ]);
                }
                else{
                    respuesta(200, ['success' => true, 'Mensaje:' => 'Rol de usuario cambiado correctamente']);
                }
            }
        }
?>
