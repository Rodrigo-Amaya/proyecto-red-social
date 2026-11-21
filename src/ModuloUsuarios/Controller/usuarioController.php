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
                if($parte === "iniciarSesion"){
                    match ($metodo){
                        'POST'    => $this->iniciarSesionUsuario(),
                        default  => noPermitido(),
                    };
                    return;
                }

                if($parte === "cambiarFoto"){
                    match ($metodo){
                        'POST'    => $this->cambiarFotoPerfil(),
                        default  => noPermitido(),
                    };
                    return;
                }

                // url: usuarios/{id}
                $idUsuario = validarId($parte, 'ID inválido');

                if (isset($partes[2])) {
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
             //Inicio de sesion
            function iniciarSesionUsuario(): void {
                if (isset($_POST["correo"]) && isset($_POST["contrasena"])) {

                    $correo = trim($_POST["correo"]);
                    $contrasena = $_POST["contrasena"];

                    $resultado = $this->modelo->verficarUsuario($correo, $contrasena);

                    switch ($resultado['estado']) {
                        case 0:

                            session_start();
                            session_regenerate_id(true);

                            $_SESSION['id_usuario'] = $resultado['usuario']['id_usuario'];
                            $_SESSION['id_rol'] = $resultado['usuario']['id_rol'];
                            $_SESSION['nombre_usuario'] = $resultado['usuario']['nombre'];

                            respuesta(200, ['success' => true,'Mensaje' => 'Inicio de sesión exitoso']);
                            return;

                        case 1:
                            respuesta(401, ['success' => false,'Mensaje' => 'Credenciales incorrectas']);
                            return;

                        case 2:
                            respuesta(403, ['success' => false,'Mensaje' => 'Usuario bloqueado']);
                            return;

                        case 3:
                            respuesta(401, ['success' => false,'Mensaje' => 'Contraseña incorrecta']);
                            return;
                    }
                }
            }

            function ingresarNuevoUsuario(): void {

                $nombre = $_POST["nombre"] ?? '';
                $correo = $_POST["correo"] ?? '';
                $contrasena = $_POST["contrasena"] ?? '';
                $contrasenaValidar = $_POST["contrasenaValidar"] ?? '';
                $nivel = $_POST["nivelEstudio"] ?? '';

                //Si alguno de los campos ingresados esta vacio
                if ( $nombre === '' || $correo === '' || $contrasena === '' || $contrasenaValidar === ''|| $nivel === '') {
                    respuesta(400, ['success' => false, 'Mensaje' => 'Todos los campos son abligatorios']);
                }

                //Si correo no tiene el formato correcto
                if(!filter_var($correo, FILTER_VALIDATE_EMAIL)){
                    respuesta(400, ['success' => false, 'Mensaje' => 'Formato de correo incorrecto']);
                }

                //Si la contraseña tiene al menos 8 caracteres, un caracter especial, numero y mayuscula
                validarContrasena($contrasena);

                //si las contraseñas no coinciden
                if($contrasena !== $contrasenaValidar){
                    respuesta(400, ['success' => false, 'Mensaje' => 'Contraseñas no coinciden']);
                }

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

                //Respuesta obtenida
                if($resultado === 1){
                    respuesta(409, ['success' => false, 'Mensaje' => 'El correo se encuentra en uso']);
                }
                else if($resultado === 2){
                    respuesta(400, ['success' => false, 'Mensaje' => 'Nivel de estudio inválido']);
                }
                else{
                    respuesta(201, ['success' => true, 'Mensaje' => 'Usuario ingresado correctamente']);
                }
            }

            function cambiarFotoPerfil(): void{
                $usuario = $_POST["idUsuario"] ?? NULL;
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
            //Agregar psoteriormente actualización por medio se session no por ID
            function actualizarusuario(int $id): void {
                $datos = leerJson();

                $nombre = $datos['nombre'] ?? '';
                $correo = $datos['correo'] ?? '';
                $contrasenaAnterior = $datos['contrasenaAnterior'] ?? '';
                $contrasenaNueva = $datos["contrasenaNueva"] ?? '';
                $contrasenaNuevaValidar = $datos["contrasenaNuevaValidar"] ?? '';
                $nivel = $datos["nivelEstudio"] ?? '';
                $foto = $datos["urlFoto"] ?? '';

                //Validaciones de campos
                validarCamposVacios([$nombre]);
                validarCamposVacios([$correo]);
                validarCamposVacios([$contrasenaAnterior]);
                validarCamposVacios([$contrasenaNueva]);
                validarCamposVacios([$contrasenaNuevaValidar]);
                validarCamposVacios([$foto]);
                validarCampoEntero($nivel);

                validarContrasena($contrasenaNueva);

                //si las contraseñas no coinciden
                if($contrasenaNueva !== $contrasenaNuevaValidar){
                    respuesta(400, ['success' => false, 'Mensaje' => 'Nuevas contraseñas no coinciden']);
                }

                //Si correo no tiene el formato correcto
                if(!filter_var($correo, FILTER_VALIDATE_EMAIL)){
                    respuesta(400, ['success' => false, 'Mensaje' => 'Formato de correo incorrecto']);
                }

                $resultado = $this->modelo->actualizarUsuario(
                    $id,
                    $nombre,
                    $correo,
                    $contrasenaAnterior,
                    $contrasenaNueva,
                    $nivel,
                    $foto
                );

                //Resultado recibido
                switch($resultado){
                    case 1:
                        respuesta(404, ['success' => false, 'Mensaje:' => 'Usuario no encontrado o bloqueado']);
                        break;
                    case 2:
                        respuesta(404, ['success' => false, 'Mensaje:' => 'Contraseña incorrecta']);
                        break;
                    case 3:
                        respuesta(404, ['success' => false, 'Mensaje:' => 'Correo ya registrado por otro usuario']);
                        break;
                    case 4:
                        respuesta(404, ['success' => false, 'Mensaje:' => 'Nivel de estudio inválido']);
                        break;
                    default:
                        respuesta(200, ['success' => true, 'Mensaje:' => 'Usuario actualizado correctamente']);
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
