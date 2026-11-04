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
                $id = $partes[1] ?? null;

                // url: /usuarios
                if ($id === null) {
                    match ($metodo) {
                        'GET'  => $this->buscarUsuario(),
                        'POST' => $this->ingresarNuevoUsuario(),
                        default => noPermitido(),
                    };
                    return;
                }

                // url: usuarios/{id}
                $idUsuario = validarId($id, 'ID inválido');

                if (isset($partes[2])) {
                    noEncontrado();
                }

                match ($metodo) {
                    'GET'    => $this->obtenerUsuario($idUsuario),
                    'PUT'    => $this->actualizarusuario($idUsuario),
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
                    respuesta(404, ['success' => false,'mensaje' => 'Paciente no encontrado']);
                }

                respuesta(200, [
                    'success' => true,
                    'Hospital' => $resultado
                ]);
            }


            //Busqueda de usuario
            function buscarUsuario(): void{
                if(isset($_GET["busqueda"])||
                isset($_GET["correo"])||
                isset($_GET["nivelEstudio"])||
                isset($_GET["orden"])){
                    $busqueda = trim($_GET["busquedaPaciente"] ?? '');
                    $correo = trim($_GET["correo"] ?? '');
                    $nivel = trim($_GET["nivelEstudio"] ?? '');
                    $orden = trim($_GET["orden"] ?? '');
                    $orden = strtoupper($orden);

                    //Validamos si algun campo se ingresado esta vacio
                    validarCamposVacios([$busqueda, $correo, $nivel, $orden]);

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

                    $resultado = $this->modelo->buscarPorFiltros(
                        $busqueda,
                        $correo,
                        $nivel,
                        $orden
                    );
                }

                //Listar todos los usuarios
                else{
                    $resultado = $this->modelo->listarUsuarios();
                }
                
                //Mensaje recibido
                if($resultado){
                    respuesta(200, ['success' => true, 'Pacientes' => $resultado]);
                }
                else{
                    respuesta(200, ['success' => true, 'Usuarios' => '']);
                }
            }

            //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //========== POST ==========
            function ingresarNuevoUsuario(): void {
                $datos = leerJson();

                $nombre = $datos["nombre"] ?? '';
                $correo = $datos["correo"] ?? '';
                $contrasena = $datos["contrasena"] ?? '';
                $contrasenaValidar = $datos["contrasenaValidar"] ?? '';
                $nivel = $datos["nivelEstudio"] ?? '';
                $foto = $datos["urlFoto"] ?? '';

                //Si alguno de los campos ingresados esta vacio
                if ( $nombre === '' || $correo === '' || $contrasena === '' || $contrasenaValidar === ''|| $nivel === '' || $foto === '') {
                    respuesta(400, ['success' => false, 'Mensaje' => 'Todos los campos son abligatorios']);
                }

                //si las contraseñas no coinciden
                if($contrasena !== $contrasenaValidar){
                    respuesta(400, ['success' => false, 'Mensaje' => 'Contraseñas no coinciden']);
                }

                //Si correo no tiene el formato correcto
                if(!filter_var($correo, FILTER_VALIDATE_EMAIL)){
                    respuesta(400, ['success' => false, 'Mensaje' => 'Formato de correo incorrecto']);
                }

                //Valores por defecto
                date_default_timezone_set('America/El_Salvador');
                $fecha = date('d/m/Y H:i:s');

                $resultado = $this->modelo->agregarUsuario(
                    $nombre,
                    $correo,
                    $contrasena,
                    $fecha,
                    $nivel, 
                    $foto
                );

                //Respuesta obtenida
                if($resultado === 1){
                    respuesta(409, ['success' => false, 'Mensaje' => 'Correo ya en uso']);
                }
                else if($resultado === 2){
                    respuesta(400, ['success' => false, 'Mensaje' => 'Nivel de estudio inválido']);
                }
                else{
                    respuesta(201, ['success' => true, 'Mensaje' => 'Usuario ingresado correctamente']);
                }
            }

            //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //========== PUT ==========
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
                        respuesta(404, ['success' => false, 'Mensaje:' => 'ID ingresado es invalido']);
                        break;
                    case 2:
                        respuesta(404, ['success' => false, 'Mensaje:' => 'Contraseña incorrecta']);
                        break;
                    case 3:
                        respuesta(404, ['success' => false, 'Mensaje:' => 'Nivel de estudi inválido']);
                        break;
                    default:
                        respuesta(200, ['success' => true, 'Mensaje:' => 'Paciente actualizado correctamente']);
                }
            }
        }
?>