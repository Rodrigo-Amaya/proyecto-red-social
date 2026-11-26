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

            // URL: /publicacion/{idPublicacion}/comentario
            if ($idPublicacion === null) {
                noEncontrado();
                return;
            }

            $idPublicacion = validarId($idPublicacion, 'ID de publicación inválido');

            // Comprobar que la ruta corresponda a comentarios
            if (($partes[2] ?? null) !== 'comentario') {
                noEncontrado();
                return;
            }

            $idComentario = $partes[3] ?? null;

            // URL: /publicacion/{idPublicacion}/comentario
            if ($idComentario === null) {
                match ($metodo) {
                    'GET'  => $this->buscarComentarios($idPublicacion),
                    'POST' => $this->ingresarNuevoComentario($idPublicacion),
                    default => noPermitido(),
                };
                return;
            }

            // URL: /publicacion/{idPublicacion}/comentario/{idComentario}
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
                return;
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
            $resultado = $this->modelo->buscarPorID(
                $idComentario,
                $idPublicacion
            );

            if (!$resultado) {
                respuesta(404, [
                    'success' => false,
                    'Mensaje' => 'Comentario no encontrado o bloqueado'
                ]);
                return;
            }

            respuesta(200, [
                'success' => true,
                'Comentario' => $resultado
            ]);
        }


        // Buscar comentarios de una publicación
        public function buscarComentarios(int $idPublicacion): void
        {
            if (isset($_GET['fecha']) || isset($_GET['likes'])) {

                $ordenFecha = strtoupper(trim($_GET['fecha'] ?? ''));
                $ordenLikes = strtoupper(trim($_GET['likes'] ?? ''));

            // Validar que no se envíen parámetros vacíos
            if (
                (isset($_GET['fecha']) && $ordenFecha === '') ||
                (isset($_GET['likes']) && $ordenLikes === '')
            ) {
                respuesta(400, [
                    'success' => false,
                    'Mensaje' => 'Los filtros enviados no pueden estar vacíos'
                ]);
                return;
            }

                // Validar orden de fecha
                if ($ordenFecha !== '' &&
                    !in_array($ordenFecha, ['ASC', 'DESC'], true)) {

                    respuesta(400, [
                        'success' => false,
                        'Mensaje' => 'Tipo de orden de fecha inválido'
                    ]);
                    return;
                }

                // Validar orden de likes
                if ($ordenLikes !== '' &&
                    !in_array($ordenLikes, ['ASC', 'DESC'], true)) {

                    respuesta(400, [
                        'success' => false,
                        'Mensaje' => 'Tipo de orden de likes inválido'
                    ]);
                    return;
                }

                $resultado = $this->modelo->ordenarPorFiltros(
                    $idPublicacion,
                    $ordenFecha ?: null,
                    $ordenLikes ?: null
                );
            }

            // Obtener todos los comentarios sin filtros
            else {
                $resultado = $this->modelo->todosComentarios(
                    $idPublicacion
                );
            }

            if ($resultado) {
                respuesta(200, [
                    'success' => true,
                    'Comentarios' => $resultado
                ]);
            }
            else {
                respuesta(200, [
                    'success' => true,
                    'Comentarios' => []
                ]);
            }
        }


        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== POST ==========

        // Registrar un nuevo comentario
        public function ingresarNuevoComentario(int $idPublicacion): void
        {
            $titulo = $_POST['titulo'] ?? '';
            $mensaje = $_POST['mensaje'] ?? '';
            $usuario = $_POST['id_usuario'] ?? '';
            $comentarioPadre = $_POST['id_comentario_padre'] ?? null;

            // Archivos adjuntos
            $archivo1 = $_FILES['archivo_1'] ?? null;
            $archivo2 = $_FILES['archivo_2'] ?? null;
            $archivo3 = $_FILES['archivo_3'] ?? null;

            $archivos = [
                $archivo1,
                $archivo2,
                $archivo3
            ];

            // Validar campos obligatorios
            if (!is_string($titulo) ||
                !is_string($mensaje) ||
                !is_string($usuario)) {

                respuesta(400, [
                    'success' => false,
                    'Mensaje' => 'Los datos enviados no son válidos'
                ]);
                return;
            }

            $titulo = trim($titulo);
            $mensaje = trim($mensaje);

            if ($titulo === '' || $mensaje === '' || $usuario === '') {
                respuesta(400, [
                    'success' => false,
                    'Mensaje' => 'Todos los campos obligatorios deben completarse'
                ]);
                return;
            }

            // Validar ID de usuario
            validarCampoEntero($usuario);

            // Validar comentario padre (si es una respuesta)
            if ($comentarioPadre === '' || $comentarioPadre === '0') {
                $comentarioPadre = null;
            }
            else if ($comentarioPadre !== null) {
                $comentarioPadre = validarId(
                    $comentarioPadre,
                    'ID de comentario padre inválido'
                );
            }

            // Validar archivos y obtener sus extensiones
            $extensiones = [];

            foreach ($archivos as $archivo) {

                // No se adjuntó ningún archivo
                if ($archivo === null ||
                    ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {

                    $extensiones[] = null;
                    continue;
                }

                $extension = validarDocumento($archivo);

                if ($extension === false) {
                    respuesta(400, [
                        'success' => false,
                        'Mensaje' => 'Tipo de archivo inválido o archivo corrupto'
                    ]);
                    return;
                }

                $extensiones[] = $extension;
            }

            // Enviar datos al modelo
            $resultado = $this->modelo->hacerComentario(
                $titulo,
                $mensaje,
                (int)$usuario,
                $idPublicacion,
                $comentarioPadre,
                $archivo1,
                $archivo2,
                $archivo3,
                $extensiones
            );

            // Interpretar resultado del modelo
            switch ($resultado) {

                case 0:
                    respuesta(201, [
                        'success' => true,
                        'Mensaje' => 'Comentario registrado correctamente'
                    ]);
                    break;

                case 1:
                    respuesta(404, [
                        'success' => false,
                        'Mensaje' => 'Usuario no encontrado o bloqueado'
                    ]);
                    break;

                case 2:
                    respuesta(404, [
                        'success' => false,
                        'Mensaje' => 'Publicación no encontrada o bloqueada'
                    ]);
                    break;

                case 3:
                    respuesta(400, [
                        'success' => false,
                        'Mensaje' => 'Comentario padre inválido¿'
                    ]);
                    break;

                case 4:
                    respuesta(400, [
                        'success' => false,
                        'Mensaje' => 'El comentario padre pertenece a otra publicación'
                    ]);
                    break;

                case 5:
                    respuesta(400, [
                        'success' => false,
                        'Mensaje' => 'El título y el mensaje son obligatorios'
                    ]);
                    break;

                case 6:
                    respuesta(400, [
                        'success' => false,
                        'Mensaje' => 'El título o el mensaje superan la longitud permitida'
                    ]);
                    break;

                case 7:
                    respuesta(500, [
                        'success' => false,
                        'Mensaje' => 'Error al guardar archivos o realizar la operación.'
                    ]);
                    break;

                default:
                    respuesta(500, [
                        'success' => false,
                        'Mensaje' => 'Error inesperado al registrar el comentario'
                    ]);
                    break;
            }
        }


        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== PUT ==========

        // Actualizar un comentario
        public function actualizarComentario(int $idPublicacion, int $idComentario): void
        {
            $datos = leerJson();

            $titulo = $datos['titulo'] ?? '';
            $mensaje = $datos['mensaje'] ?? '';
            $usuario = $datos['usuario'] ?? '';

            // Validar tipos de datos
            if (!is_string($titulo) ||
                !is_string($mensaje) ||
                !is_scalar($usuario)) {

                respuesta(400, [
                    'success' => false,
                    'Mensaje' => 'Los datos enviados no son válidos'
                ]);
                return;
            }

            $titulo = trim($titulo);
            $mensaje = trim($mensaje);

            // Validar campos obligatorios
            validarCamposVacios([
                $titulo,
                $mensaje,
                $usuario
            ]);

            // Validar usuario
            validarCampoEntero($usuario);

            // Verificar que el comentario pertenezca a la publicación indicada
            $comentario = $this->modelo->buscarPorID(
                $idComentario,
                $idPublicacion
            );

            if (!$comentario) {
                respuesta(404, [
                    'success' => false,
                    'Mensaje' => 'Comentario no encontrado o bloqueado'
                ]);
                return;
            }

            // Enviar datos al modelo
            $resultado = $this->modelo->actualizarComentario(
                $idComentario,
                $titulo,
                $mensaje,
                (int)$usuario
            );

            switch ($resultado) {

                case 0:
                    respuesta(200, [
                        'success' => true,
                        'Mensaje' => 'Comentario actualizado correctamente'
                    ]);
                    break;

                case 1:
                    respuesta(404, [
                        'success' => false,
                        'Mensaje' => 'Comentario no encontrado o bloqueado'
                    ]);
                    break;

                case 2:
                    respuesta(404, [
                        'success' => false,
                        'Mensaje' => 'Usuario no encontrado o bloqueado'
                    ]);
                    break;

                case 3:
                    respuesta(403, [
                        'success' => false,
                        'Mensaje' => 'No tienes permiso para modificar este comentario'
                    ]);
                    break;

                case 6:
                    respuesta(400, [
                        'success' => false,
                        'Mensaje' => 'El título y el mensaje son obligatorios'
                    ]);
                    break;

                case 7:
                    respuesta(400, [
                        'success' => false,
                        'Mensaje' => 'El título o el mensaje superan la longitud permitida'
                    ]);
                    break;

                default:
                    respuesta(500, [
                        'success' => false,
                        'Mensaje' => 'Error inesperado al actualizar el comentario'
                    ]);
                    break;
            }
        }


        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== DELETE ==========

        // Eliminar (bloquear) un comentario
        public function eliminarComentario(int $idPublicacion, int $idComentario): void
        {
            $datos = leerJson();

            $usuario = $datos['usuario'] ?? '';

            // Validar usuario
            validarCamposVacios([$usuario]);
            validarCampoEntero($usuario);

            // Verificar que el comentario pertenezca a la publicación indicada
            $comentario = $this->modelo->buscarPorID(
                $idComentario,
                $idPublicacion
            );

            if (!$comentario) {
                respuesta(404, [
                    'success' => false,
                    'Mensaje' => 'Comentario no encontrado o bloqueado'
                ]);
                return;
            }

            // Verificar que el usuario sea el propietario
            if ((int)$comentario['id_usuario'] !== (int)$usuario) {
                respuesta(403, [
                    'success' => false,
                    'Mensaje' => 'No tienes permiso para eliminar este comentario'
                ]);
                return;
            }

            $resultado = $this->modelo->eliminarComentario(
                $idComentario,
                (int)$usuario
            );

            if ($resultado === true) {
                respuesta(200, [
                    'success' => true,
                    'Mensaje' => 'Comentario eliminado correctamente'
                ]);
            }
            else {
                respuesta(404, [
                    'success' => false,
                    'Mensaje' => 'Comentario o usuario no encontrado, o bloqueado'
                ]);
            }
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