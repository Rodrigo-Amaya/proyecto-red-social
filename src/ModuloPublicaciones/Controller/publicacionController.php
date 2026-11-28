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
                if ($metodo === 'POST' && $id !== null && strtoupper($_GET['_method'] ?? '') === 'PUT') {
                    $metodo = 'PUT';
                }

                // url: publicaciones/feed
                if ($id === 'feed') {
                    if (isset($partes[2])) {
                        noEncontrado();
                    }
                    if ($metodo !== 'GET') {
                        noPermitido();
                    }
                    $this->obtenerFeed();
                    return;
                }

                // URL: /publicacion/guardadas
                if ($id === 'guardadas') {

                    if (isset($partes[2])) {
                        noEncontrado();
                        return;
                    }

                    if ($metodo !== 'GET') {
                        noPermitido();
                        return;
                    }

                    $this->obtenerPublicacionesGuardadas();
                    return;
                }

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

                // URL: /publicacion/{idPublicacion}/guardar
                if (($partes[2] ?? null) === 'guardar') {

                    if (isset($partes[3])) {
                        noEncontrado();
                        return;
                    }

                    match ($metodo) {
                        'POST'   => $this->guardarPublicacion($idPublicacion),
                        'DELETE' => $this->quitarPublicacionGuardada($idPublicacion),
                        default  => noPermitido(),
                    };

                    return;
                }

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
            // Feed paginado (Limite 50, por defecto 15)
            function obtenerFeed(): void {
                $limite = 15;
                if (isset($_GET['limite']) && $_GET['limite'] !== '') {
                    $limite = filter_var($_GET['limite'], FILTER_VALIDATE_INT);
                    if ($limite === false || $limite < 1 || $limite > 50) {
                        respuesta(400, ['success' => false, 'mensaje' => 'El límite debe ser un número entre 1 y 50']);
                    }
                }

                $antesDe = null;
                if (isset($_GET['antes_de']) && $_GET['antes_de'] !== '') {
                    $antesDe = validarCampoEntero($_GET['antes_de']);
                }

                $materia = null;
                if (isset($_GET['materia']) && $_GET['materia'] !== '') {
                    $materia = validarCampoEntero($_GET['materia']);
                }

                $feed = $this->modelo->feed($limite, $antesDe, $materia);
                $publicaciones = $feed['publicaciones'];
                $ultima = end($publicaciones);

                respuesta(200, [
                    'success' => true,
                    'mensaje' => 'Feed obtenido correctamente',
                    'publicaciones' => $publicaciones,
                    'hay_mas' => $feed['hay_mas'],
                    'siguiente_cursor' => $ultima ? (int)$ultima['id_publicacion'] : null
                ]);
            }

            //busqueda de publicacion por id
            function obtenerPublicacion(int $id): void{
                $resultado = $this->modelo->buscarPorID($id);

                //Sino hay resultado
                if (!$resultado) {
                    respuesta(404, ['success' => false,'mensaje' => 'Publicacion no encontrada o bloqueada']);
                }

                //Permisos solo para mostrar u ocultar botones (la ruta es pública)
                $usuario  = isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : null;
                $permisos = $this->modelo->permisosSobre($id, $usuario);
                $resultado['puede_editar']   = $permisos['puede_editar'];
                $resultado['puede_eliminar'] = $permisos['puede_eliminar'];

                respuesta(200, [
                    'success' => true,
                    'mensaje' => 'Publicación obtenida correctamente',
                    'publicacion' => $resultado
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
                    //Los filtros deben ser texto
                    foreach (['busqueda', 'usuario', 'materia', 'estado', 'orden', 'bloqueado'] as $filtro) {
                        if (isset($_GET[$filtro]) && !is_string($_GET[$filtro])) {
                            respuesta(400, ['success' => false, 'mensaje' => 'Los filtros enviados no son válidos']);
                        }
                    }

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
                            respuesta(400, ['success' => false, 'mensaje' => 'Tipo de estado invalido']); // CORREGIDO: clave
                        }
                    }

                    //Validamos el orden
                    if($orden){
                        if($orden !== 'ASC' && $orden !== 'DESC'){
                            respuesta(400, ['success' => false, 'mensaje' => 'Tipo de orden inválido']); // CORREGIDO: clave
                        }
                    }

                     //bloqueado: 0 es publico, 1 solo administrador
                    if ($bloqueado !== '') {
                        if ($bloqueado !== '0' && $bloqueado !== '1') {
                            respuesta(400, ['success' => false, 'mensaje' => 'El filtro bloqueado debe ser 0 o 1']);
                        }

                        if ($bloqueado === '1') {
                            $idSolicitante = usuarioActual(); // 401 sin sesion
                            if (!$this->modelo->esAdministrador($idSolicitante)) {
                                respuesta(403, ['success' => false, 'mensaje' => 'No tienes permiso para ver publicaciones bloqueadas']);
                            }
                        }
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
                
                respuesta(200, [
                    'success' => true,
                    'mensaje' => 'Publicaciones obtenidas correctamente',
                    'publicaciones' => $resultado ?: []
                ]);
            }

            //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //========== PUBLICACIONES GUARDADAS ==========

            // Obtener publicaciones guardadas
            public function obtenerPublicacionesGuardadas(): void {
                $usuario = usuarioActual();

                $resultado = $this->modelo->publicacionesGuardadas($usuario);

                if ($resultado === false) {
                    respuesta(404, ['success' => false, 'mensaje' => 'Usuario no encontrado o bloqueado']);
                }

                respuesta(200, [
                    'success' => true,
                    'mensaje' => 'Publicaciones guardadas obtenidas correctamente',
                    'publicaciones' => $resultado
                ]);
            }


            // Guardar publicación
            public function guardarPublicacion(int $idPublicacion): void {
                $usuario = usuarioActual();

                $resultado = $this->modelo->guardarPublicacion($usuario, $idPublicacion);

                match ($resultado) {
                    0       => respuesta(201, ['success' => true, 'mensaje' => 'Publicación guardada correctamente']),
                    1       => respuesta(404, ['success' => false, 'mensaje' => 'Usuario no encontrado o bloqueado']),
                    2       => respuesta(404, ['success' => false, 'mensaje' => 'Publicación no encontrada o bloqueada']),
                    3       => respuesta(409, ['success' => false, 'mensaje' => 'La publicación ya se encuentra guardada']),
                    default => respuesta(500, ['success' => false, 'mensaje' => 'Error inesperado al guardar la publicación']),
                };
            }


            // Quitar publicación de guardados
            public function quitarPublicacionGuardada(int $idPublicacion): void {
                $usuario = usuarioActual();

                $resultado = $this->modelo->quitarPublicacionGuardada($usuario, $idPublicacion);

                match ($resultado) {
                    0       => respuesta(200, ['success' => true, 'mensaje' => 'Publicación eliminada de guardados correctamente']),
                    1       => respuesta(404, ['success' => false, 'mensaje' => 'Usuario no encontrado o bloqueado']),
                    2       => respuesta(404, ['success' => false, 'mensaje' => 'La publicación no se encuentra en los guardados del usuario']),
                    default => respuesta(500, ['success' => false, 'mensaje' => 'Error inesperado al quitar la publicación de guardados']),
                };
            }

            //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //========== POST ==========
            function ingresarNuevaPublicacion(): void {

                $usuario = usuarioActual();

                $titulo  = $_POST["titulo"] ?? '';
                $mensaje = $_POST["mensaje"] ?? '';
                $materia = $_POST["id_materia"] ?? '';

                //Los datos deben ser texto (si llegan como arreglo, es una peticion invalida)
                if (!is_string($titulo) || !is_string($mensaje) || !is_string($materia)) {
                    respuesta(400, ['success' => false, 'mensaje' => 'Los datos enviados no son válidos']);
                }

                $titulo  = trim($titulo);
                $mensaje = trim($mensaje);
                $materia = trim($materia);

                //Si alguno de los campos ingresados esta vacio
                if ($titulo === '' || $mensaje === '' || $materia === '') {
                    respuesta(400, ['success' => false, 'mensaje' => 'Título, mensaje y materia son obligatorios']);
                }

                //Límites por caracteres (coinciden con la columna del titulo)
                if (mb_strlen($titulo) > 200) {
                    respuesta(400, ['success' => false, 'mensaje' => 'El título no puede superar los 200 caracteres']);
                }

                if (mb_strlen($mensaje) > 5000) {
                    respuesta(400, ['success' => false, 'mensaje' => 'El mensaje no puede superar los 5000 caracteres']);
                }

                //Que la materia sea un numero entero valido
                $materia = validarCampoEntero($materia);

                //Archivos adjuntos (maximo 3)
                $archivos    = [];
                $extensiones = [];

                for ($n = 1; $n <= 3; $n++) {
                    $archivo = $_FILES["archivo_$n"] ?? null;

                    //Sin archivo en esta posicion
                    if ($archivo === null || (!is_array($archivo['error'] ?? null) && ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE)) {
                        $archivos[]    = null;
                        $extensiones[] = null;
                        continue;
                    }

                    //Archivo mayor al limite del servidor
                    if (!is_array($archivo['error']) && in_array($archivo['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                        respuesta(400, ['success' => false, 'mensaje' => "El archivo_$n es demasiado grande"]);
                    }

                    $extension = validarDocumento($archivo);

                    if ($extension === false) {
                        respuesta(400, ['success' => false, 'mensaje' => "El archivo_$n no es válido, supera los 10 MB o está corrupto"]);
                    }

                    $archivos[]    = $archivo;
                    $extensiones[] = $extension;
                }

                $resultado = $this->modelo->hacerPublicacion(
                    $titulo,
                    $mensaje,
                    $usuario,
                    $materia,
                    $archivos[0],
                    $archivos[1],
                    $archivos[2],
                    $extensiones
                );

                //Respuesta obtenida
                match ($resultado) {
                    0       => respuesta(201, ['success' => true, 'mensaje' => 'Se realizó la publicación con éxito']),
                    1       => respuesta(404, ['success' => false, 'mensaje' => 'Usuario no encontrado o bloqueado']),
                    2       => respuesta(400, ['success' => false, 'mensaje' => 'Materia no encontrada']),
                    3       => respuesta(500, ['success' => false, 'mensaje' => 'Error al guardar la publicación o los documentos']),
                    default => respuesta(500, ['success' => false, 'mensaje' => 'Error inesperado al publicar']),
                };
            }

            //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //========== PUT ==========
            //Agregar psoteriormente actualización por medio se session no por ID
            function actualizarPublicacion(int $id): void {
                $usuario = usuarioActual();

                $titulo  = $_POST['titulo'] ?? '';
                $mensaje = $_POST['mensaje'] ?? '';
                $materia = $_POST['id_materia'] ?? '';

                //Los datos deben ser texto (si llegan como arreglo, es una peticion invalida)
                if (!is_string($titulo) || !is_string($mensaje) || !is_string($materia)) {
                    respuesta(400, ['success' => false, 'mensaje' => 'Los datos enviados no son válidos']);
                }

                $titulo  = trim($titulo);
                $mensaje = trim($mensaje);
                $materia = trim($materia);

                //Si alguno de los campos ingresados esta vacio
                if ($titulo === '' || $mensaje === '' || $materia === '') {
                    respuesta(400, ['success' => false, 'mensaje' => 'Título, mensaje y materia son obligatorios']);
                }

                //Límites por caracteres (iguales a los del POST)
                if (mb_strlen($titulo) > 200) {
                    respuesta(400, ['success' => false, 'mensaje' => 'El título no puede superar los 200 caracteres']);
                }

                if (mb_strlen($mensaje) > 5000) {
                    respuesta(400, ['success' => false, 'mensaje' => 'El mensaje no puede superar los 5000 caracteres']);
                }

                $materia = validarCampoEntero($materia);

                $archivosNuevos = [];     // posicion => archivo subido
                $posicionesEliminar = []; // posiciones a borrar

                for ($n = 1; $n <= 3; $n++) {
                    $archivo = $_FILES["archivo_$n"] ?? null;

                    if ($archivo !== null) {
                        //Un arreglo anidado (archivo_1[]) no es una subida valida
                        if (is_array($archivo['error'] ?? null)) {
                            respuesta(400, ['success' => false, 'mensaje' => "El archivo_$n no es válido"]);
                        }
                        $error = $archivo['error'] ?? UPLOAD_ERR_NO_FILE;
                    } else {
                        $error = UPLOAD_ERR_NO_FILE;
                    }

                    // Se subió un archivo en esta posición: se valida ANTES de tocar nada
                    if ($error !== UPLOAD_ERR_NO_FILE) {
                        if (in_array($error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                            respuesta(400, ['success' => false, 'mensaje' => "El archivo_$n es demasiado grande"]);
                        }
                        if (validarDocumento($archivo) === false) {
                            respuesta(400, ['success' => false, 'mensaje' => "El archivo_$n no es válido, supera los 10 MB o está corrupto"]);
                        }
                        $archivosNuevos[$n] = $archivo;
                    }
                    // Si no subió archivo pero pidió eliminar esa posición
                    elseif (($_POST["eliminar_$n"] ?? '') === '1') {
                        $posicionesEliminar[] = $n;
                    }
                }

                $resultado = $this->modelo->actualizarPublicacion(
                    $id,
                    $titulo,
                    $mensaje,
                    $materia,
                    $archivosNuevos,
                    $posicionesEliminar,
                    $usuario
                );

                match ($resultado) {
                    0       => respuesta(200, ['success' => true, 'mensaje' => 'Publicación actualizada correctamente']),
                    1       => respuesta(404, ['success' => false, 'mensaje' => 'Publicación no encontrada o bloqueada']),
                    2       => respuesta(400, ['success' => false, 'mensaje' => 'No se encontró la materia']),
                    3       => respuesta(403, ['success' => false, 'mensaje' => 'Ya no se puede modificar la publicación']),
                    4       => respuesta(500, ['success' => false, 'mensaje' => 'Error al guardar los cambios o los documentos']),
                    5       => respuesta(403, ['success' => false, 'mensaje' => 'No tienes permiso para modificar esta publicación']),
                    6       => respuesta(404, ['success' => false, 'mensaje' => 'Usuario no encontrado o bloqueado']),
                    default => respuesta(500, ['success' => false, 'mensaje' => 'Error inesperado al actualizar la publicación']),
                };
            }

            //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //========== DELETE ==========
            function eliminarPublicacion(int $id): void {
                $usuario = usuarioActual();

                $resultado = $this->modelo->eliminarPubliacion($id, $usuario);

                match ($resultado) {
                    0       => respuesta(200, ['success' => true, 'mensaje' => 'Publicación eliminada correctamente']),
                    1       => respuesta(404, ['success' => false, 'mensaje' => 'La publicación no fue encontrada o se encuentra bloqueada']),
                    2       => respuesta(404, ['success' => false, 'mensaje' => 'Usuario no encontrado o bloqueado']),
                    3       => respuesta(403, ['success' => false, 'mensaje' => 'No tienes permiso para eliminar esta publicación']),
                    default => respuesta(500, ['success' => false, 'mensaje' => 'Error inesperado al eliminar la publicación']),
                };
            }

            //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //========== PATCH ==========
            function cambiarEstado(int $id): void {
                $usuario = usuarioActual();
                $datos   = leerJson();

                $estado = $datos['estado'] ?? '';

                if (!is_string($estado)) {
                    respuesta(400, ['success' => false, 'mensaje' => 'El estado debe ser texto']);
                }

                $estado = strtoupper(trim($estado));

                validarCamposVacios([$estado]);

                if($estado !== 'SINRESOLVER' && $estado !== 'RESUELTA'){
                    respuesta(400, ['success' => false, 'mensaje' => 'Tipo de estado inválido']);
                }

                $resultado = $this->modelo->cambiarEstadoPublicacion($id, $estado, $usuario);

                match ($resultado) {
                    0       => respuesta(200, ['success' => true, 'mensaje' => 'El estado de la publicación fue actualizado correctamente']),
                    1       => respuesta(404, ['success' => false, 'mensaje' => 'Publicación no encontrada o bloqueada']),
                    2       => respuesta(404, ['success' => false, 'mensaje' => 'Usuario no encontrado o bloqueado']),
                    3       => respuesta(403, ['success' => false, 'mensaje' => 'No tienes permiso para cambiar el estado de esta publicación']),
                    default => respuesta(500, ['success' => false, 'mensaje' => 'Error inesperado al cambiar el estado']),
                };
            }
        }
?>