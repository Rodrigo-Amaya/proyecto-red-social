<?php 
    require_once __DIR__ . '/notificacion.php';

    class Comentario {
        private mysqli $conn;
        
        public function __construct($dbConnection){
            $this->conn = $dbConnection;
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== GET ==========

        public function todosComentarios($idPublicacion){
            $sql = "SELECT c.id_comentario, c.titulo, c.fecha, c.mensaje, c.id_usuario, c.id_publicacion, c.id_comentario_padre, c.bloquear,

            (SELECT a.url_archivo FROM archivos_comentarios a WHERE a.id_comentario = c.id_comentario ORDER BY a.id_archivo ASC LIMIT 1) AS archivo_1,

            (SELECT a.url_archivo FROM archivos_comentarios a WHERE a.id_comentario = c.id_comentario ORDER BY a.id_archivo ASC LIMIT 1 OFFSET 1) AS archivo_2,

            (SELECT a.url_archivo FROM archivos_comentarios a WHERE a.id_comentario = c.id_comentario ORDER BY a.id_archivo ASC LIMIT 1 OFFSET 2) AS archivo_3

            FROM comentarios c
            WHERE c.id_publicacion = ? AND c.bloquear = 0;";
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                throw new RuntimeException($this->conn->error);
            }
            $stmt->bind_param("i", $idPublicacion);
            $stmt->execute();
            $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $resultado;            
        }

        //---Busqueda por ID---
        public function buscarPorID($id, $idPublicacion){
            $sql = ("SELECT c.id_comentario, c.titulo, c.fecha, c.mensaje, c.id_usuario, c.id_publicacion, c.id_comentario_padre, c.bloquear,

            (SELECT a.url_archivo FROM archivos_comentarios a WHERE a.id_comentario = c.id_comentario ORDER BY a.id_archivo ASC LIMIT 1) AS archivo_1,

            (SELECT a.url_archivo FROM archivos_comentarios a WHERE a.id_comentario = c.id_comentario ORDER BY a.id_archivo ASC LIMIT 1 OFFSET 1) AS archivo_2,

            (SELECT a.url_archivo FROM archivos_comentarios a WHERE a.id_comentario = c.id_comentario ORDER BY a.id_archivo ASC LIMIT 1 OFFSET 2) AS archivo_3

            FROM comentarios c
            WHERE c.id_comentario = ? AND c.id_publicacion = ? AND c.bloquear = 0;");
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                throw new RuntimeException($this->conn->error);
            }
            $stmt->bind_param("ii", $id, $idPublicacion); 
            if (!$stmt->execute()) {
                $stmt->close();
                return null;
            }
            $fila = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $fila;
        }

        //---Ordenación mediante filtros---
        public function ordenarPorFiltros($idPublicacion, $ordenFecha, $ordenLikes){
            //Lista blanca estricta solo acepta ASC o DESC
            $dirLikes = in_array($ordenLikes, ['ASC', 'DESC'], true) ? $ordenLikes : null;
            $dirFecha = in_array($ordenFecha, ['ASC', 'DESC'], true) ? $ordenFecha : null;

            $sql = "SELECT c.id_comentario, c.titulo, c.fecha, c.mensaje, c.id_usuario, c.id_publicacion, c.id_comentario_padre, c.bloquear,

                (SELECT a.url_archivo FROM archivos_comentarios a WHERE a.id_comentario = c.id_comentario ORDER BY a.id_archivo ASC LIMIT 1) AS archivo_1,

                (SELECT a.url_archivo FROM archivos_comentarios a WHERE a.id_comentario = c.id_comentario ORDER BY a.id_archivo ASC LIMIT 1 OFFSET 1) AS archivo_2,

                (SELECT a.url_archivo FROM archivos_comentarios a WHERE a.id_comentario = c.id_comentario ORDER BY a.id_archivo ASC LIMIT 1 OFFSET 2) AS archivo_3,

                (SELECT COUNT(*) FROM likes_comentario l WHERE l.id_comentario = c.id_comentario) AS total_likes

                FROM comentarios c

                WHERE c.id_publicacion = ? AND c.bloquear = 0";

            $orden = [];

            if ($dirLikes !== null) {
                $orden[] = "total_likes " . $dirLikes;
            }
            if ($dirFecha !== null) {
                $orden[] = "c.fecha " . $dirFecha;
            }

            if (!empty($orden)) {
                $sql .= " ORDER BY " . implode(", ", $orden);
            }

            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                throw new RuntimeException('Error al preparar la consulta: ' . $this->conn->error);
            }
            $stmt->bind_param("i", $idPublicacion);
            $stmt->execute();
            $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $resultado;
        }
        
        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== POST ==========
        public function hacerComentario(
            string $titulo, 
            string $mensaje, 
            int $usuario, 
            int $publicacion, 
            ?int $comentarioPadre, 
            ?array $archivo1, 
            ?array $archivo2, 
            ?array $archivo3, 
            array $extenciones): int {
            $comentarioPadre = ($comentarioPadre === null || $comentarioPadre === '' || $comentarioPadre === 0) ? null : (int) $comentarioPadre;

            $titulo  = trim($titulo);
            $mensaje = trim($mensaje);

            if ($titulo === '' || $mensaje === '') {
                return 6;
            }
            if (mb_strlen($titulo) > 200 || mb_strlen($mensaje) > 5000) {
                return 7;
            }
            
            $validacion = $this->validarPOST($usuario, $publicacion, $comentarioPadre);

            //Si pasa todas las validaciones
            if($validacion !== 0){
                return $validacion;
            }

            //valores por defecto
            date_default_timezone_set('America/El_Salvador');
            $fecha = date('Y-m-d H:i:s');

            $this->conn->begin_transaction();
            $rutasSubidas = [];
            
            try {
                $sql = "INSERT INTO comentarios 
                (titulo, fecha, mensaje, id_usuario, id_publicacion, id_comentario_padre) VALUES (?,?,?,?,?,?)";
                $stmt = $this->conn->prepare($sql);
                if ($stmt === false) {
                    throw new RuntimeException($this->conn->error);
                }
                $stmt->bind_param("sssiii", $titulo, $fecha, $mensaje, $usuario, $publicacion, $comentarioPadre);
                if (!$stmt->execute()) {
                    throw new RuntimeException($stmt->error);
                }
                $idComentario = $this->conn->insert_id;
                $stmt->close();

                $archivos = [$archivo1, $archivo2, $archivo3];

                //Para cada archivo se guarda
                foreach ($archivos as $indice => $archivo) {
                    //Si no se envió un archivo
                    if (!is_array($archivo) || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    $ruta = $this->guardarDocumento($archivo, $idComentario, $indice + 1);

                    if ($ruta === false) {
                        throw new RuntimeException('Error guardando archivo ' . ($indice + 1));
                    }
                    $rutasSubidas[] = $ruta;

                    $tipoArchivo = $extenciones[$indice] ?? '';
                    $nombre = basename($ruta);

                    $sql = "INSERT INTO archivos_comentarios 
                    (id_comentario, url_archivo, nombre_archivo, tipo_archivo, fecha_subida) 
                    VALUES (?,?,?,?,?)";

                    $stmt = $this->conn->prepare($sql);
                    if ($stmt === false) {
                        throw new RuntimeException($this->conn->error);
                    }
                    $stmt->bind_param("issss", $idComentario, $ruta, $nombre, $tipoArchivo, $fecha);
                    if (!$stmt->execute()) {
                        throw new RuntimeException($stmt->error);
                    }
                    $stmt->close();
                }

                $this->conn->commit();

                //Notificar al autor de la publicacion (si falla, el comentario ya quedo guardado)
                try {
                    (new Notificacion($this->conn))->notificarComentario($idComentario, $publicacion, $usuario);
                } catch (Throwable $e) {
                    error_log('No se pudo crear la notificación de comentario: ' . $e->getMessage());
                }

                return 0;

            } catch (Throwable $e) {
                $this->conn->rollback();
                // Borrar archivos físicos ya subidos
                foreach ($rutasSubidas as $r) {
                    if (is_file($r)) {
                        @unlink($r);
                    }
                }
                return 8;
            }
        }    
        
        //Validaciones para POST
        public function validarPOST($usuario, $publicacion, $comentarioPadre){

            //validar si el usaurio existe y no este bloqueado
            $sql = "SELECT id_usuario FROM usuarios WHERE id_usuario = ? AND bloqueo_usuario = 0";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $usuario); 
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                $stmt->close();
                return 1;
            }

            //validar si la publicación existe y no está bloqueada
            $sql = "SELECT id_publicacion FROM publicaciones WHERE id_publicacion = ? AND bloquear = 0";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $publicacion); 
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                $stmt->close();
                return 2;
            }

            //validar si el comentario padre existe
            if ($comentarioPadre !== null) {
                $sql = "SELECT id_publicacion, bloquear FROM comentarios WHERE id_comentario = ? AND bloquear = 0";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("i", $comentarioPadre);
                $stmt->execute();
                $padre = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$padre) {
                    return 3;
                }
                if ((int)$padre['id_publicacion'] !== (int)$publicacion) {
                    return 4;
                }
                // bloquear = 0 ya está en el WHERE, así que el return 5 es redundante
            }
            
            //Si es valido
            return 0;
        }

        //Guardamos el documento en la carpeta correspondiente
        private function guardarDocumento($archivo, $idComentario, $numero): string|false{
            //Obtenemos la extencion del archivo
            $extencion = validarDocumento($archivo);

            //Si no regreso una extencion
            if ($extencion === false) {
                return false;
            }

            $directorio = __DIR__ . '/../../../documentos/comentarios/';
            $subdirectorio = '';

            if ($extencion === "pdf") {
                $subdirectorio = 'pdf/';
            }
            else if (in_array($extencion, ["jpg", "png", "webp"])) {
                $subdirectorio = 'imagenes/';
            }
            else {
                $subdirectorio = 'office/';
            }

            $directorio = __DIR__ . '/../../../documentos/comentarios/' . $subdirectorio;

            // Crear directorio si no existe...
            if (!is_dir($directorio)) {
                if (!mkdir($directorio, 0755, true) && !is_dir($directorio)) {
                    return false; // no se pudo crear el directorio
                }
            }

            $nombre = "documento_{$idComentario}_{$numero}.{$extencion}";

            $rutaFisica = $directorio . $nombre;

            if (!move_uploaded_file($archivo['tmp_name'], $rutaFisica)) {
                return false;
            }

            return "documentos/comentarios/" . $subdirectorio . $nombre;
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== PUT ==========
        public function actualizarComentario($idComentario, $titulo, $mensaje, $usuario){
            
            $titulo  = trim($titulo);
            $mensaje = trim($mensaje);

            if ($titulo === '' || $mensaje === '') {
                return 6;
            }
            if (mb_strlen($titulo) > 200 || mb_strlen($mensaje) > 5000) {
                return 7;
            }
            $validacion = $this->validarPUT($idComentario, $usuario);
            
            //si pasa todas las validaciones
            if($validacion !== 0){
                return $validacion;
            }

            $sql = "UPDATE comentarios SET titulo = ?, mensaje = ? WHERE id_comentario = ?";
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                throw new RuntimeException($this->conn->error);
            }            
            $stmt->bind_param("ssi", $titulo, $mensaje, $idComentario); 
            if (!$stmt->execute()) {
                throw new RuntimeException($stmt->error);
            }
            $stmt->close();

            return 0;
        }

        //validaciones para PUT
        public function validarPUT($idComentario, $usuario){
            // 1. Comentario existe y no bloqueado
            $sql = "SELECT id_usuario FROM comentarios WHERE id_comentario = ? AND bloquear = 0";
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                throw new RuntimeException($this->conn->error);
            }
            $stmt->bind_param("i", $idComentario);
            $stmt->execute();
            $comentario = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$comentario) {
                return 1; // comentario no existe o bloqueado
            }

            // 2. Usuario existe y no bloqueado
            $sql = "SELECT id_usuario FROM usuarios WHERE id_usuario = ? AND bloqueo_usuario = 0";
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                throw new RuntimeException($this->conn->error);
            }
            $stmt->bind_param("i", $usuario);
            $stmt->execute();
            $usuarioExiste = $stmt->get_result()->num_rows > 0;
            $stmt->close();

            if (!$usuarioExiste) {
                return 2; // usuario no existe o bloqueado
            }

            // 3. ¿Es el dueño del comentario?
            if ((int)$comentario['id_usuario'] !== (int)$usuario) {
                return 3; // no es el dueño
            }

            return 0;
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== DELETE ==========
        public function eliminarComentario($idComentario, $usuario){
            $validacion = $this->validarDELETE($idComentario, $usuario);
            
            //si pasa todas las validaciones
            if($validacion !== true){
                return $validacion;
            }

            $sql = "UPDATE comentarios SET bloquear = 1 WHERE id_comentario = ?";
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                throw new RuntimeException($this->conn->error);
            } 
            $stmt->bind_param("i", $idComentario);
            if (!$stmt->execute()) {
                throw new RuntimeException($stmt->error);
            } 
            $stmt->close();

            return true;
        }

        //validaciones DELETE
        public function validarDELETE($idComentario, $usuario){

            // 1. Comentario existe y no bloqueado
            $sql = "SELECT id_usuario FROM comentarios WHERE id_comentario = ? AND bloquear = 0";
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                throw new RuntimeException($this->conn->error);
            }
            $stmt->bind_param("i", $idComentario);
            $stmt->execute();
            $comentario = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$comentario) {
                return false; // comentario no existe o ya bloqueado
            }

            // 2. Usuario existe y no bloqueado
            $sql = "SELECT id_usuario FROM usuarios WHERE id_usuario = ? AND bloqueo_usuario = 0";
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                throw new RuntimeException($this->conn->error);
            }
            $stmt->bind_param("i", $usuario);
            $stmt->execute();
            $usuarioExiste = $stmt->get_result()->num_rows > 0;
            $stmt->close();

            if (!$usuarioExiste) {
                return false; // usuario no existe o bloqueado
            }

            // 3. ¿Es el dueño?
            if ((int)$comentario['id_usuario'] !== (int)$usuario) {
                return false; // no es el dueño
            }

            return true;
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== LIKES ==========
        // 0 = ok | 1 = comentario no encontrado o bloqueado | 2 = usuario no encontrado o bloqueado | 3 = ya habia dado like
        public function darLike(int $idComentario, int $idPublicacion, int $usuario): int {
            if (!$this->buscarPorID($idComentario, $idPublicacion)) {
                return 1;
            }
            if (!$this->usuarioActivo($usuario)) {
                return 2;
            }

            date_default_timezone_set('America/El_Salvador');
            $fecha = date('Y-m-d H:i:s');

            $stmt = $this->conn->prepare("INSERT IGNORE INTO likes_comentario (id_usuario, id_comentario, fecha) VALUES (?,?,?)");
            $stmt->bind_param("iis", $usuario, $idComentario, $fecha);
            $stmt->execute();
            $nuevo = $stmt->affected_rows > 0;
            $stmt->close();

            if (!$nuevo) {
                return 3;
            }

            //Notificar al autor del comentario (si falla, el like ya quedo guardado)
            try {
                (new Notificacion($this->conn))->notificarLikeComentario($idComentario, $usuario);
            } catch (Throwable $e) {
                error_log('No se pudo crear la notificación de like: ' . $e->getMessage());
            }

            return 0;
        }

        // 0 = ok | 1 = comentario no encontrado o bloqueado | 3 = no habia dado like
        public function quitarLike(int $idComentario, int $idPublicacion, int $usuario): int {
            if (!$this->buscarPorID($idComentario, $idPublicacion)) {
                return 1;
            }

            $stmt = $this->conn->prepare("DELETE FROM likes_comentario WHERE id_usuario = ? AND id_comentario = ?");
            $stmt->bind_param("ii", $usuario, $idComentario);
            $stmt->execute();
            $borrado = $stmt->affected_rows > 0;
            $stmt->close();

            if (!$borrado) {
                return 3;
            }

            try {
                (new Notificacion($this->conn))->retirarLikeComentario($idComentario, $usuario);
            } catch (Throwable $e) {
                error_log('No se pudo retirar la notificación de like: ' . $e->getMessage());
            }

            return 0;
        }

        public function totalLikes(int $idComentario): int {
            $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM likes_comentario WHERE id_comentario = ?");
            $stmt->bind_param("i", $idComentario);
            $stmt->execute();
            $total = (int)$stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();
            return $total;
        }

        private function usuarioActivo(int $usuario): bool {
            $stmt = $this->conn->prepare("SELECT id_usuario FROM usuarios WHERE id_usuario = ? AND bloqueo_usuario = 0");
            $stmt->bind_param("i", $usuario);
            $stmt->execute();
            $existe = $stmt->get_result()->num_rows > 0;
            $stmt->close();
            return $existe;
        }
    }