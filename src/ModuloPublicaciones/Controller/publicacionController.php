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
                    respuesta(404, ['success' => false,'mensaje' => 'Publicacion no encontrada o bloqueada']); // CORREGIDO: concordancia
                }

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

                    //Validar permisos de admin para ver ocultos
                    if($bloqueado){
                        validarCampoEntero($bloqueado);
                        validarTrueOFalse($bloqueado);
                        respuesta(403, ['success' => false, 'mensaje' => 'No tienes permiso para ver publicaciones bloqueadas']); // NUEVO
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

                $usuario = $_GET['id_usuario'] ?? '';

                if (!is_scalar($usuario) || trim((string)$usuario) === '') {
                    respuesta(400, [
                        'success' => false,
                        'mensaje' => 'El ID del usuario es obligatorio'
                    ]);
                    return;
                }

                validarCampoEntero($usuario);

                $resultado = $this->modelo->publicacionesGuardadas(
                    (int)$usuario
                );

                if ($resultado === false) {
                    respuesta(404, [
                        'success' => false,
                        'mensaje' => 'Usuario no encontrado o bloqueado'
                    ]);
                    return;
                }

                respuesta(200, [
                    'success' => true,
                    'mensaje' => 'Publicaciones guardadas obtenidas correctamente',
                    'publicaciones' => $resultado
                ]);
            }


            // Guardar publicación
            public function guardarPublicacion(int $idPublicacion): void {

                $datos = leerJson();

                $usuario = $datos['id_usuario'] ?? '';

                if (!is_scalar($usuario) || trim((string)$usuario) === '') {
                    respuesta(400, [
                        'success' => false,
                        'mensaje' => 'El ID del usuario es obligatorio'
                    ]);
                    return;
                }

                validarCampoEntero($usuario);

                $resultado = $this->modelo->guardarPublicacion(
                    (int)$usuario,
                    $idPublicacion
                );

                switch ($resultado) {

                    case 0:
                        respuesta(201, [
                            'success' => true,
                            'mensaje' => 'Publicación guardada correctamente'
                        ]);
                        break;

                    case 1:
                        respuesta(404, [
                            'success' => false,
                            'mensaje' => 'Usuario no encontrado o bloqueado'
                        ]);
                        break;

                    case 2:
                        respuesta(404, [
                            'success' => false,
                            'mensaje' => 'Publicación no encontrada o bloqueada'
                        ]);
                        break;

                    case 3:
                        respuesta(409, [
                            'success' => false,
                            'mensaje' => 'La publicación ya se encuentra guardada'
                        ]);
                        break;
                }
            }


            // Quitar publicación de guardados
            public function quitarPublicacionGuardada(int $idPublicacion): void {

                $datos = leerJson();

                $usuario = $datos['id_usuario'] ?? '';

                if (!is_scalar($usuario) || trim((string)$usuario) === '') {
                    respuesta(400, [
                        'success' => false,
                        'mensaje' => 'El ID del usuario es obligatorio'
                    ]);
                    return;
                }

                validarCampoEntero($usuario);

                $resultado = $this->modelo->quitarPublicacionGuardada(
                    (int)$usuario,
                    $idPublicacion
                );

                switch ($resultado) {

                    case 0:
                        respuesta(200, [
                            'success' => true,
                            'mensaje' => 'Publicación eliminada de guardados correctamente'
                        ]);
                        break;

                    case 1:
                        respuesta(404, [
                            'success' => false,
                            'mensaje' => 'Usuario no encontrado o bloqueado'
                        ]);
                        break;

                    case 2:
                        respuesta(404, [
                            'success' => false,
                            'mensaje' => 'La publicación no se encuentra en los guardados del usuario'
                        ]);
                        break;
                }
            }

            //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //========== POST ==========
            function ingresarNuevaPublicacion(): void {

                $titulo = $_POST["titulo"] ?? '';
                $mensaje = $_POST["mensaje"] ?? '';
                $usuario = $_POST["id_usuario"] ?? '';
                $materia = $_POST["id_materia"] ?? '';
                $archivo1 = $_FILES["archivo_1"] ?? NULL;
                $archivo2 = $_FILES["archivo_2"] ?? NULL;
                $archivo3 = $_FILES["archivo_3"] ?? NULL;
                $archivos = [$archivo1, $archivo2, $archivo3];

                //Si alguno de los campos ingresados esta vacio
                if ( $titulo === '' || $mensaje === '' || $usuario === '' || $materia === '') {
                    respuesta(400, ['success' => false, 'mensaje' => 'Todos los campos son abligatorios']);
                }

                //Que el titulo no se amayor a 100 caracteres
                if(str_word_count($titulo) > 100){
                    respuesta(400, ['success' => false, 'mensaje' => 'Titulo muy largo']);
                }

                //Que el mensaje no se amayor a 500 caracteres
                if(str_word_count($mensaje) > 500){
                    respuesta(400, ['success' => false, 'mensaje' => 'Mensaje muy largo']);
                }

                //Que el usuario no sea texto
                validarCampoEntero($usuario);

                //Que la materia no sea texto
                validarCampoEntero($materia);

                $extensiones = [];

                //Para los archivos
                foreach($archivos as $archivo){
                    if($archivo === NULL){
                        $extensiones[] = null;
                        continue;
                    }
                    $extension = validarDocumento($archivo);

                    if($extension === false){
                        respuesta(400, ['success' => false, 'mensaje' => 'Tipo de archivo no es valido o esta corrupto']);
                    }

                    $extensiones[] = $extension;
                }

                $resultado = $this->modelo->hacerPublicacion(
                    $titulo,
                    $mensaje,
                    $usuario,
                    $materia,
                    $archivo1,
                    $archivo2,
                    $archivo3,
                    $extensiones
                );

                //Respuesta obtenida
                if($resultado === 1){
                    respuesta(409, ['success' => false, 'mensaje' => 'Usuario no encontrado o bloqueado']);
                }
                else if($resultado === 2){
                    respuesta(400, ['success' => false, 'mensaje' => 'Materia no encontrada']);
                }
                else if ($resultado === 3) {
                    respuesta(500, ['success' => false, 'mensaje' => 'Error al guardar los documentos']);
                }
                else{
                    respuesta(201, ['success' => true, 'mensaje' => 'Se realizo la publicacion con exito']);
                }
            }

            //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //========== PUT ==========
            //Agregar psoteriormente actualización por medio se session no por ID
            function actualizarPublicacion(int $id): void {
                $titulo  = trim($_POST['titulo'] ?? '');
                $mensaje = trim($_POST['mensaje'] ?? '');
                $materia = trim($_POST['id_materia'] ?? '');
                
                //Si alguno de los campos ingresados esta vacio
                if ( $titulo === '' || $mensaje === '' || $materia === '') {
                    respuesta(400, ['success' => false, 'mensaje' => 'Titulo, mensaje y materia son obligatorios']);
                }

                if (str_word_count($titulo) > 100) {
                    respuesta(400, ['success' => false, 'mensaje' => 'Titulo muy largo']);
                }

                if (str_word_count($mensaje) > 500) {
                    respuesta(400, ['success' => false, 'mensaje' => 'Mensaje muy largo']);
                }

                validarCampoEntero($materia);

                $archivosNuevos = [];   // posicion => archivo subido
                $posicionesEliminar = []; // posiciones a borrar

                for ($n = 1; $n <= 3; $n++) {
                    $archivo = $_FILES["archivo_$n"] ?? null;

                    // Se subió un archivo en esta posición: se valida ANTES de tocar nada
                    if ($archivo !== null && $archivo['error'] !== UPLOAD_ERR_NO_FILE) {
                        if (validarDocumento($archivo) === false) {
                            respuesta(400, ['success' => false, 'mensaje' => "El archivo_$n no es valido o esta corrupto"]);
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
                    $posicionesEliminar
                );

                switch ($resultado) {
                    case 1:
                        respuesta(404, ['success' => false, 'mensaje' => 'Publicación no encontrada o bloqueada']);
                        break;
                    case 2:
                        respuesta(400, ['success' => false, 'mensaje' => 'No se encontro la materia']);
                        break;
                    case 3:
                        respuesta(403, ['success' => false, 'mensaje' => 'Ya no se puede modificar la publicación']);
                        break;
                    case 4:
                        respuesta(500, ['success' => false, 'mensaje' => 'Error al guardar los cambios o los documentos']);
                        break;
                    default:
                        respuesta(200, ['success' => true, 'mensaje' => 'Publicación actualizada correctamente']);
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
                    respuesta(404, ['success' => false,'mensaje' => 'La publicación no fue encontrada o se encuentra bloqueada'
                    ]);
                }
                else{
                    respuesta(200, ['success' => true, 'mensaje' => 'Publicación eliminada correctamente']);
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
                    respuesta(400, ['success' => false, 'mensaje' => 'Tipo de estado inválido']);
                }

                $resultado = $this->modelo->cambiarEstadoPublicacion(
                    $id,
                    $estado
                );

                //Resultado recibido
                if($resultado === false){
                    respuesta(404, ['success' => false,'mensaje' => 'Publicación no encontrada o bloqueada'
                    ]);
                }
                else{
                    respuesta(200, ['success' => true, 'mensaje' => 'El estado de la publicación fue actualizado correctamente']);
                }
            }
        }
?>