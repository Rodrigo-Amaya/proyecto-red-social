<?php
    class ComentarioController implements Controlador
    {
        public function __construct(
            private Comentario $modelo
        ) {}

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== CONTROL DE METODOS ==========

        public function control(string $metodo, array $partes): void
        {
            $idPublicacion = $partes[1] ?? null;

            // URL: /publicaciones/{idPublicacion}/comentario
            if ($idPublicacion === null) {
                noEncontrado();
            }

            $idPublicacion = validarId($idPublicacion, 'ID de publicación inválido');

            if (($partes[2] ?? null) !== 'comentario') {
                noEncontrado();
            }

            $idComentario = $partes[3] ?? null;

            // URL: /publicaciones/{idPublicacion}/comentario
            if ($idComentario === null) {
                match ($metodo) {
                    'GET'   => $this->buscarComentarios($idPublicacion),
                    'POST'  => $this->ingresarNuevoComentario($idPublicacion),
                    default => noPermitido(),
                };
                return;
            }

            // URL: /publicaciones/{idPublicacion}/comentario/{idComentario}
            $idComentario = validarId($idComentario, 'ID de comentario inválido');

            // URL: /publicacion/{idPublicacion}/comentario/{idComentario}/like
            if (($partes[4] ?? null) === 'like' && !isset($partes[5])) {
                match ($metodo) {
                    'POST'   => $this->darLike($idPublicacion, $idComentario),
                    'DELETE' => $this->quitarLike($idPublicacion, $idComentario),
                    default  => noPermitido(),
                };
                return;
            }

            if (isset($partes[4])) {
                noEncontrado();
            }

            match ($metodo) {
                'GET'    => $this->obtenerComentario($idPublicacion, $idComentario),
                'PUT'    => $this->actualizarComentario($idPublicacion, $idComentario),
                'DELETE' => $this->eliminarComentario($idPublicacion, $idComentario),
                default  => noPermitido(),
            };
        }


        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== GET ==========

        // Obtener un comentario por ID
        public function obtenerComentario(int $idPublicacion, int $idComentario): void
        {
            $resultado = $this->modelo->buscarPorID($idComentario, $idPublicacion);

            if (!$resultado) {
                respuesta(404, ['success' => false, 'mensaje' => 'Comentario no encontrado o bloqueado']);
            }

            respuesta(200, [
                'success'    => true,
                'mensaje'    => 'Comentario obtenido correctamente',
                'comentario' => $resultado
            ]);
        }


        // Listar comentarios de una publicación
        public function buscarComentarios(int $idPublicacion): void
        {
            if (isset($_GET['fecha']) || isset($_GET['likes'])) {
                // Los filtros deben ser texto
                if (
                    (isset($_GET['fecha']) && !is_string($_GET['fecha'])) ||
                    (isset($_GET['likes']) && !is_string($_GET['likes']))
                ) {
                    respuesta(400, ['success' => false, 'mensaje' => 'Los filtros enviados no son válidos']);
                }

                $ordenFecha = strtoupper(trim($_GET['fecha'] ?? ''));
                $ordenLikes = strtoupper(trim($_GET['likes'] ?? ''));

                if (
                    (isset($_GET['fecha']) && $ordenFecha === '') ||
                    (isset($_GET['likes']) && $ordenLikes === '')
                ) {
                    respuesta(400, ['success' => false, 'mensaje' => 'Los filtros enviados no pueden estar vacíos']);
                }

                if ($ordenFecha !== '' && !in_array($ordenFecha, ['ASC', 'DESC'], true)) {
                    respuesta(400, ['success' => false, 'mensaje' => 'Tipo de orden de fecha inválido']);
                }

                if ($ordenLikes !== '' && !in_array($ordenLikes, ['ASC', 'DESC'], true)) {
                    respuesta(400, ['success' => false, 'mensaje' => 'Tipo de orden de likes inválido']);
                }

                $resultado = $this->modelo->ordenarPorFiltros(
                    $idPublicacion,
                    $ordenFecha ?: null,
                    $ordenLikes ?: null
                );
            } else {
                $resultado = $this->modelo->todosComentarios($idPublicacion);
            }

            respuesta(200, [
                'success'     => true,
                'mensaje'     => 'Comentarios obtenidos correctamente',
                'comentarios' => $resultado ?: []
            ]);
        }


        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== POST ==========

        // Registrar un nuevo comentario (Requiere sesion)
        public function ingresarNuevoComentario(int $idPublicacion): void
        {
            $usuario = usuarioActual();

            $titulo          = $_POST['titulo'] ?? '';
            $mensaje         = $_POST['mensaje'] ?? '';
            $comentarioPadre = $_POST['id_comentario_padre'] ?? null;

            if (!is_string($titulo) || !is_string($mensaje)) {
            respuesta(400, ['success' => false, 'mensaje' => 'Los datos enviados no son válidos']);
        }

            $titulo  = trim($titulo);
            $mensaje = trim($mensaje);

            // Validar mensaje
            if ($mensaje === '') {
                respuesta(400, ['success' => false, 'mensaje' => 'El mensaje es obligatorio']);
            }

            // Comentario padre
            if ($comentarioPadre === '' || $comentarioPadre === '0') {
                $comentarioPadre = null;
            } elseif ($comentarioPadre !== null) {
                $comentarioPadre = validarId($comentarioPadre, 'ID de comentario padre inválido');
            }

            // Archivos adjuntos (Maximo 3)
            $archivo1 = $_FILES['archivo_1'] ?? null;
            $archivo2 = $_FILES['archivo_2'] ?? null;
            $archivo3 = $_FILES['archivo_3'] ?? null;

            $extensiones = [];

            foreach ([$archivo1, $archivo2, $archivo3] as $archivo) {
                if ($archivo === null || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    $extensiones[] = null;
                    continue;
                }

                $extension = validarDocumento($archivo);

                if ($extension === false) {
                    respuesta(400, ['success' => false, 'mensaje' => 'Tipo de archivo inválido o archivo corrupto']);
                }

                $extensiones[] = $extension;
            }

            $resultado = $this->modelo->hacerComentario(
                $titulo,
                $mensaje,
                $usuario,
                $idPublicacion,
                $comentarioPadre,
                $archivo1,
                $archivo2,
                $archivo3,
                $extensiones
            );

            if ($resultado === 0) {
                respuesta(201, ['success' => true, 'mensaje' => 'Comentario registrado correctamente']);
            }

            $errores = [
                1 => [404, 'Usuario no encontrado o bloqueado'],
                2 => [404, 'Publicación no encontrada o bloqueada'],
                3 => [400, 'Comentario padre inválido'],
                4 => [400, 'El comentario padre pertenece a otra publicación'],
                5 => [400, 'El mensaje es obligatorio'],
                6 => [400, 'El título o el mensaje superan la longitud permitida'],
                7 => [500, 'Error al guardar archivos o realizar la operación'],
            ];

            [$codigo, $textoError] = $errores[$resultado] ?? [500, 'Error inesperado al registrar el comentario'];
            respuesta($codigo, ['success' => false, 'mensaje' => $textoError]);
        }


        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== PUT ==========

        // Actualizar un comentario (Requiere sesion)
        public function actualizarComentario(int $idPublicacion, int $idComentario): void
        {
            $usuario = usuarioActual();
            $datos   = leerJson();

            $titulo  = $datos['titulo'] ?? '';
            $mensaje = $datos['mensaje'] ?? '';

            if (!is_string($titulo) || !is_string($mensaje)) {
                respuesta(400, ['success' => false, 'mensaje' => 'Los datos enviados no son válidos']);
            }

            $titulo  = trim($titulo);
            $mensaje = trim($mensaje);

            if ($mensaje === '') {
                respuesta(400, ['success' => false, 'mensaje' => 'El mensaje es obligatorio']);
            }

            // El comentario debe pertenecer a la publicación indicada en la URL
            $comentario = $this->modelo->buscarPorID($idComentario, $idPublicacion);

            if (!$comentario) {
                respuesta(404, ['success' => false, 'mensaje' => 'Comentario no encontrado o bloqueado']);
            }

            $resultado = $this->modelo->actualizarComentario($idComentario, $titulo, $mensaje, $usuario);

            if ($resultado === 0) {
                respuesta(200, ['success' => true, 'mensaje' => 'Comentario actualizado correctamente']);
            }

            $errores = [
                1 => [404, 'Comentario no encontrado o bloqueado'],
                2 => [404, 'Usuario no encontrado o bloqueado'],
                3 => [403, 'No tienes permiso para modificar este comentario'],
                4 => [403, 'Ya no se puede modificar el comentario'],
                5 => [400, 'El mensaje es obligatorio'],
                6 => [400, 'El título o el mensaje superan la longitud permitida'],
            ];

            [$codigo, $textoError] = $errores[$resultado] ?? [500, 'Error inesperado al actualizar el comentario'];
            respuesta($codigo, ['success' => false, 'mensaje' => $textoError]);
        }


        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== DELETE ==========

        // Eliminar/Bloquear un comentario (Requiere permisos de sesion) 
        public function eliminarComentario(int $idPublicacion, int $idComentario): void
        {
            $usuario = usuarioActual();
            $comentario = $this->modelo->buscarPorID($idComentario, $idPublicacion);

            if (!$comentario) {
                respuesta(404, ['success' => false, 'mensaje' => 'Comentario no encontrado o bloqueado']);
            }

            if ((int)$comentario['id_usuario'] !== $usuario) {
                respuesta(403, ['success' => false, 'mensaje' => 'No tienes permiso para eliminar este comentario']);
            }

            $resultado = $this->modelo->eliminarComentario($idComentario, $usuario);

            if ($resultado === true) {
                respuesta(200, ['success' => true, 'mensaje' => 'Comentario eliminado correctamente']);
            }

            respuesta(404, ['success' => false, 'mensaje' => 'Comentario o usuario no encontrado, o bloqueado']);
        }


        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== LIKES (usuario tomado de la sesion) ==========

        // POST /publicacion/{id}/comentario/{idComentario}/like
        public function darLike(int $idPublicacion, int $idComentario): void
        {
            $usuario = usuarioActual();

            switch ($this->modelo->darLike($idComentario, $idPublicacion, $usuario)) {
                case 0:
                    respuesta(201, [
                        'success' => true,
                        'Mensaje' => 'Like registrado',
                        'total_likes' => $this->modelo->totalLikes($idComentario)
                    ]);
                    break;
                case 1:
                    respuesta(404, ['success' => false, 'Mensaje' => 'Comentario no encontrado o bloqueado']);
                    break;
                case 2:
                    respuesta(404, ['success' => false, 'Mensaje' => 'Usuario no encontrado o bloqueado']);
                    break;
                case 3:
                    respuesta(409, ['success' => false, 'Mensaje' => 'Ya le diste like a este comentario']);
                    break;
                default:
                    respuesta(500, ['success' => false, 'Mensaje' => 'Error inesperado al registrar el like']);
            }
        }

        // DELETE /publicacion/{id}/comentario/{idComentario}/like
        public function quitarLike(int $idPublicacion, int $idComentario): void
        {
            $usuario = usuarioActual();

            switch ($this->modelo->quitarLike($idComentario, $idPublicacion, $usuario)) {
                case 0:
                    respuesta(200, [
                        'success' => true,
                        'Mensaje' => 'Like retirado',
                        'total_likes' => $this->modelo->totalLikes($idComentario)
                    ]);
                    break;
                case 1:
                    respuesta(404, ['success' => false, 'Mensaje' => 'Comentario no encontrado o bloqueado']);
                    break;
                case 3:
                    respuesta(404, ['success' => false, 'Mensaje' => 'No habías dado like a este comentario']);
                    break;
                default:
                    respuesta(500, ['success' => false, 'Mensaje' => 'Error inesperado al retirar el like']);
            }
        }
    }
?>