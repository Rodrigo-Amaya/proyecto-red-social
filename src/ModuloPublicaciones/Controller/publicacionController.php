<?php

    class PublicacionController implements Controlador
        {
            public function __construct(
                private Publicacion $modelo
            ) {}

            //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //========== CONTROL DE METODOS ==========
            public function control(string $metodo, array $partes): void
            {
                $id = $partes[1] ?? null;

                // url: /publicacion
                if ($id === null) {
                    match ($metodo) {
                        'GET'  => $this->buscarPublicacion(),
                        'POST' => $this->ingresarNuevaPublicacion(),
                        default => noPermitido(),
                    };
                    return;
                }

                // url: usuarios/{id}
                $idPublicacion = validarId($id, 'ID inválido');

                if (isset($partes[2])) {
                    noEncontrado();
                }

                match ($metodo) {
                    'GET'    => $this->obtenerPublicacion($idPublicacion),
                    'PUT'    => $this->actualizarPublicacion($idPublicacion),
                    'DELETE' => $this->eliminarPublicacion($idPublicacion),
                    'PATCH'  => $this->cambiarEstado($idPublicacion),
                    default  => noPermitido(),
                };
            }

            //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //========== GET ==========

            //busqueda de publicacion por id
            function obtenerPublicacion(int $id): void{
                $resultado = $this->modelo->buscarPorID($id);

                //Sino hay resultado
                if (!$resultado) {
                    respuesta(404, ['success' => false,'mensaje' => 'Publicacion no encontrado o bloqueada']);
                }

                respuesta(200, [
                    'success' => true,
                    'Usuarios' => $resultado
                ]);
            }


            //Busqueda de publicacion/es
            function buscarPublicacion(): void{
                if(isset($_GET["busqueda"])||
                isset($_GET["usuario"])||
                isset($_GET["materia"])||
                isset($_GET["estado"]) ||
                isset($_GET["orden"]) ||
                isset($_GET["bloqueado"])){
                    $busqueda = trim($_GET["busqueda"] ?? '');
                    $usuario = trim($_GET["usuario"] ?? '');
                    $materia = trim($_GET["materia"] ?? '');
                    $estado = trim($_GET["estado"] ?? '');
                    $estado = strtoupper($estado);
                    $orden = trim($_GET["orden"] ?? '');
                    $orden = strtoupper($orden);
                    $bloqueado = trim($_GET["bloqueado"] ?? '');

                    //Validamos si algun campo se ingresado esta vacio
                    validarCamposVacios([$busqueda, $usuario, $materia, $estado, $orden, $bloqueado]);

                    //Validamos si la id de usaurio es un numero entero
                    if($usuario){
                        validarCampoEntero($usuario);
                    }
                    
                    //Validamos si la id de materia es un numero entero
                    if($materia){
                        validarCampoEntero($materia);
                    }

                    //Validar estado sea uno correcto
                    if($estado){
                        if($estado !== 'SINRESOLVER' && $estado !== 'RESUELTA'){
                            respuesta(400, ['success' => false, 'Mensaje' => 'Tipo de estado invalido']);
                            exit;
                        }
                    }

                    //Validamos el orden
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
                        $usuario, 
                        $materia, 
                        $estado,
                        $orden,
                        $bloqueado
                    );
                }

                //Mostrar todas las publicaciones
                else{
                    $resultado = $this->modelo->todasPublicaciones();
                }
                
                //Mensaje recibido
                if($resultado){
                    respuesta(200, ['success' => true, 'Publicaciones' => $resultado]);
                }
                else{
                    respuesta(200, ['success' => true, 'Publicaciones' => '']);
                }
            }

            //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //========== POST ==========
            function ingresarNuevaPublicacion(): void {
                $datos = leerJson();

                $titulo = $datos["titulo"] ?? '';
                $mensaje = $datos["mensaje"] ?? '';
                $usuario = $datos["usuario"] ?? '';
                $materia = $datos["materia"] ?? '';

                //Si alguno de los campos ingresados esta vacio
                if ( $titulo === '' || $mensaje === '' || $usuario === '' || $materia === '') {
                    respuesta(400, ['success' => false, 'Mensaje' => 'Todos los campos son abligatorios']);
                }

                //Que el titulo no se amayor a 100 caracteres
                if(str_word_count($titulo) > 100){
                    respuesta(400, ['success' => false, 'Mensaje' => 'Titulo muy largo']);
                }

                //Que el mensaje no se amayor a 500 caracteres
                if(str_word_count($mensaje) > 500){
                    respuesta(400, ['success' => false, 'Mensaje' => 'Mensaje muy largo']);
                }

                //Que el usuario no sea texto
                validarCampoEntero($usuario);

                //Que la materia no sea texto
                validarCampoEntero($materia);

                $resultado = $this->modelo->hacerPublicacion(
                    $titulo,
                    $mensaje,
                    $usuario,
                    $materia
                );

                //Respuesta obtenida
                if($resultado === 1){
                    respuesta(409, ['success' => false, 'Mensaje' => 'Usuario no encontrado o bloqueado']);
                }
                else if($resultado === 2){
                    respuesta(400, ['success' => false, 'Mensaje' => 'Materia no encontrada']);
                }
                else{
                    respuesta(201, ['success' => true, 'Mensaje' => 'Se realizo la publicacion con exito']);
                }
            }

            //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //========== PUT ==========
            //Agregar psoteriormente actualización por medio se session no por ID
            function actualizarPublicacion(int $id): void {
                $datos = leerJson();

                $titulo = $datos['titulo'] ?? '';
                $mensaje = $datos['mensaje'] ?? '';
                $usuario = $datos['usuario'] ?? '';
                $materia = $datos["materia"] ?? '';

                //Validaciones de campos que fueron modificados
                validarCamposVacios([$titulo]);
                validarCamposVacios([$mensaje]);
                validarCamposVacios([$usuario]);
                validarCamposVacios([$materia]);

                //Que el titulo no se amayor a 100 caracteres
                if(str_word_count($titulo) > 100){
                    respuesta(400, ['success' => false, 'Mensaje' => 'Titulo muy largo']);
                }

                //Que el mensaje no se amayor a 500 caracteres
                if(str_word_count($mensaje) > 500){
                    respuesta(400, ['success' => false, 'Mensaje' => 'Mensaje muy largo']);
                }

                //Que el usuario no sea texto
                validarCampoEntero($usuario);

                //Que la materia no sea texto
                validarCampoEntero($materia);

                $resultado = $this->modelo->actualizarPublicacion(
                    $id,
                    $titulo,
                    $mensaje,
                    $usuario,
                    $materia
                );

                //Resultado recibido
                switch($resultado){
                    case 1:
                        respuesta(404, ['success' => false, 'Mensaje:' => 'Publicación no encontrada o bloqueada']);
                        break;
                    case 2:
                        respuesta(404, ['success' => false, 'Mensaje:' => 'Usuario no encontrado o bloqueado']);
                        break;
                    case 3:
                        respuesta(404, ['success' => false, 'Mensaje:' => 'Usuario no coincide con el que publico']);
                        break;
                    case 4:
                        respuesta(404, ['success' => false, 'Mensaje:' => 'No se encontro la materia']);
                        break;
                    case 5:
                        respuesta(404, ['success' => false, 'Mensaje:' => 'Ya no se puede modificar la publicación']);
                        break;
                    default:
                        respuesta(200, ['success' => true, 'Mensaje:' => 'Publicación actualizada correctamente']);
                }
            }

            //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //========== DELETE ==========
            function eliminarPublicacion(int $id): void {
                $resultado = $this->modelo->eliminarPubliacion(
                    $id
                );

                //Resultado recibido
                if($resultado === false){
                    respuesta(404, ['success' => false,'Mensaje' => 'La publicación no fue encontrada o se encuentra bloqueada'
                    ]);
                }
                else{
                    respuesta(200, ['success' => true, 'Mensaje:' => 'Publicación eliminada correctamente']);
                }
            }

            //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //========== PATCH ==========
            function cambiarEstado(int $id): void {
                $datos = leerJson();

                $estado = $datos['estado'] ?? '';
                $estado = strtoupper($estado);

                validarCamposVacios([$estado]);
                
                if($estado !== 'SINRESOLVER' && $estado !== 'RESUELTA'){
                    respuesta(400, ['success' => false, 'Mensaje' => 'Tipo de estado inválido']);
                    exit;
                }

                $resultado = $this->modelo->cambiarEstadoPublicacion(
                    $id,
                    $estado
                );

                //Resultado recibido
                if($resultado === false){
                    respuesta(404, ['success' => false,'Mensaje' => 'Publicación no encontrada o bloqueada'
                    ]);
                }
                else{
                    respuesta(200, ['success' => true, 'Mensaje:' => 'El estado de la publicación actualizado correctamente']);
                }
            }
        }
?>