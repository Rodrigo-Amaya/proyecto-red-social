<?php 
    class Publicacion {
        private mysqli $conn;
        
        public function __construct($dbConnection){
            $this->conn = $dbConnection;
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== GET ==========
        /**
         * Devolver todas las publicaciones, paginadas y ordenadas desde la más reciente
         * @return array Lista de publicaciones
         */
        public function feed(int $limite, ?int $antesDe, ?int $idMateria) : array{
            $sql = "SELECT p.id_publicacion, p.titulo, p.fecha, p.mensaje, p.puntos, p.id_usuario, p.id_materia, p.estado,

            u.nombre AS autor,
            u.url_foto,
            n.nombre AS nivel,
            m.nombre AS materia,

            (SELECT COUNT(*) FROM comentarios c
             INNER JOIN usuarios uc ON uc.id_usuario = c.id_usuario
             WHERE c.id_publicacion = p.id_publicacion AND c.bloquear = 0 AND uc.bloqueo_usuario = 0) AS total_comentarios,

            (SELECT a.url_archivo FROM archivos_publicaciones a WHERE a.id_publicacion = p.id_publicacion ORDER BY a.id_archivo ASC LIMIT 1) AS archivo_1,

            (SELECT a.url_archivo FROM archivos_publicaciones a WHERE a.id_publicacion = p.id_publicacion ORDER BY a.id_archivo ASC LIMIT 1 OFFSET 1) AS archivo_2,

            (SELECT a.url_archivo FROM archivos_publicaciones a WHERE a.id_publicacion = p.id_publicacion ORDER BY a.id_archivo ASC LIMIT 1 OFFSET 2) AS archivo_3

            FROM publicaciones p
            INNER JOIN usuarios u ON u.id_usuario = p.id_usuario
            INNER JOIN nivel_estudio n ON n.id_nivel = u.id_nivel
            INNER JOIN materias m ON m.id_materia = p.id_materia
            WHERE p.bloquear = 0 AND u.bloqueo_usuario = 0";

            $parametros = [];
            $tipos = "";

            //filtro por materia
            if ($idMateria !== null) {
                $sql .= " AND p.id_materia = ?";
                $parametros[] = $idMateria;
                $tipos .= "i";
            }

            //cursor: publicaciones antes de la ultima recibida
            if ($antesDe !== null) {
                $sql .= " AND p.id_publicacion < ?";
                $parametros[] = $antesDe;
                $tipos .= "i";
            }

            $sql .= " ORDER BY p.id_publicacion DESC LIMIT ?";
            $parametros[] = $limite + 1; //Paginacion
            $tipos .= "i";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param($tipos, ...$parametros);
            $stmt->execute();
            $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            $hayMas = count($filas) > $limite;
            if ($hayMas) {
                $filas = array_slice($filas, 0, $limite);
            }

            return ['publicaciones' => $filas, 'hay_mas' => $hayMas];
        }

        public function todasPublicaciones(){
            $sql = "SELECT p.id_publicacion, p.titulo, p.fecha, p.mensaje, p.puntos, p.id_usuario, p.id_materia, p.estado,

            (SELECT a.url_archivo FROM archivos_publicaciones a WHERE a.id_publicacion = p.id_publicacion ORDER BY a.id_archivo ASC LIMIT 1) AS archivo_1,

            (SELECT a.url_archivo FROM archivos_publicaciones a WHERE a.id_publicacion = p.id_publicacion ORDER BY a.id_archivo ASC LIMIT 1 OFFSET 1) AS archivo_2,

            (SELECT a.url_archivo FROM archivos_publicaciones a WHERE a.id_publicacion = p.id_publicacion ORDER BY a.id_archivo ASC LIMIT 1 OFFSET 2) AS archivo_3

            FROM publicaciones p
            WHERE p.bloquear = 0;";
            $publicaciones = $this->conn->query($sql);
            return $publicaciones->fetch_all(MYSQLI_ASSOC);
        }

        //---Busqueda por ID---
        public function buscarPorID($id){
            $sql = ("SELECT p.id_publicacion, p.titulo, p.fecha, p.mensaje, p.puntos, p.id_usuario, p.id_materia, p.estado,

            u.nombre AS autor,
            u.url_foto,
            n.nombre AS nivel,
            m.nombre AS materia,

            (SELECT COUNT(*) FROM comentarios c
             INNER JOIN usuarios uc ON uc.id_usuario = c.id_usuario
             WHERE c.id_publicacion = p.id_publicacion AND c.bloquear = 0 AND uc.bloqueo_usuario = 0) AS total_comentarios,

            (SELECT a.url_archivo FROM archivos_publicaciones a WHERE a.id_publicacion = p.id_publicacion ORDER BY a.id_archivo ASC LIMIT 1) AS archivo_1,

            (SELECT a.url_archivo FROM archivos_publicaciones a WHERE a.id_publicacion = p.id_publicacion ORDER BY a.id_archivo ASC LIMIT 1 OFFSET 1) AS archivo_2,

            (SELECT a.url_archivo FROM archivos_publicaciones a WHERE a.id_publicacion = p.id_publicacion ORDER BY a.id_archivo ASC LIMIT 1 OFFSET 2) AS archivo_3,

            (SELECT a.nombre_archivo FROM archivos_publicaciones a WHERE a.id_publicacion = p.id_publicacion ORDER BY a.id_archivo ASC LIMIT 1) AS archivo_1_nombre,

            (SELECT a.nombre_archivo FROM archivos_publicaciones a WHERE a.id_publicacion = p.id_publicacion ORDER BY a.id_archivo ASC LIMIT 1 OFFSET 1) AS archivo_2_nombre,

            (SELECT a.nombre_archivo FROM archivos_publicaciones a WHERE a.id_publicacion = p.id_publicacion ORDER BY a.id_archivo ASC LIMIT 1 OFFSET 2) AS archivo_3_nombre

            FROM publicaciones p
            INNER JOIN usuarios u ON u.id_usuario = p.id_usuario
            INNER JOIN nivel_estudio n ON n.id_nivel = u.id_nivel
            INNER JOIN materias m ON m.id_materia = p.id_materia
            WHERE p.id_publicacion = ? AND p.bloquear = 0 AND u.bloqueo_usuario = 0;");
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id); 
            
            if($stmt->execute()){
                return $stmt->get_result()->fetch_assoc(); 
            }
            return null;
        }

        //---Busqueda mediante filtros---
        public function buscarPorFiltros($busqueda, $usuario, $materia, $estado, $orden, $bloqueado){
            $sql = ("SELECT p.id_publicacion, p.titulo, p.fecha, p.mensaje, p.puntos, p.id_usuario, p.id_materia, p.estado,

            (SELECT a.url_archivo FROM archivos_publicaciones a WHERE a.id_publicacion = p.id_publicacion ORDER BY a.id_archivo ASC LIMIT 1) AS archivo_1,

            (SELECT a.url_archivo FROM archivos_publicaciones a WHERE a.id_publicacion = p.id_publicacion ORDER BY a.id_archivo ASC LIMIT 1 OFFSET 1) AS archivo_2,

            (SELECT a.url_archivo FROM archivos_publicaciones a WHERE a.id_publicacion = p.id_publicacion ORDER BY a.id_archivo ASC LIMIT 1 OFFSET 2) AS archivo_3

            FROM publicaciones p

            WHERE 1=1");
            $parametros = [];
            $tipoParametro = "";

            //busqueda por estado de bloqueo 
            if ($bloqueado !== ''){
                $sql .= " AND p.bloquear = ?";
                $parametros[] = $bloqueado;
                $tipoParametro .= "i";
            }
            else{
                $sql .= " AND p.bloquear = 0";
            }

            //busqueda por titulo
            if ($busqueda !== ''){
                $sql .= " AND p.titulo LIKE ? ";
                $parametros[] = "%".$busqueda."%";
                $tipoParametro .= "s";
            }

            //busqueda por usuario
            if ($usuario !== ''){
                $sql .= " AND p.id_usuario = ?";
                $parametros[] = $usuario;
                $tipoParametro .= "i";
            }

            //busqueda por materia
            if ($materia !== ''){
                $sql .= " AND p.id_materia = ?";
                $parametros[] = $materia;
                $tipoParametro .= "i";
            }

            //busqueda por estado
            if ($estado !== ''){
                $sql .= " AND p.estado = ?";
                $parametros[] = $estado;
                $tipoParametro .= "s";
            }

            //ordenar de forma
            if ($orden === 'ASC'){
                $sql .= " ORDER BY p.fecha ASC";
            }
            if($orden === 'DESC'){
                $sql .= " ORDER BY p.fecha DESC";
            }

            //ejecutamos el script SQL
            $stmt = $this->conn->prepare($sql);
            if (!empty($parametros)) {
                $stmt->bind_param($tipoParametro, ...$parametros);
            }
            $stmt->execute();

            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== PUBLICACIONES GUARDADAS ==========

        // Validar que el usuario exista y no esté bloqueado
        private function usuarioActivo(int $idUsuario): bool {
            $sql = "SELECT id_usuario
                    FROM usuarios
                    WHERE id_usuario = ? AND bloqueo_usuario = 0";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $idUsuario);
            $stmt->execute();

            $existe = $stmt->get_result()->num_rows > 0;
            $stmt->close();

            return $existe;
        }


        // Guardar una publicación
        public function guardarPublicacion(int $idUsuario, int $idPublicacion): int {

            // Validar usuario
            if (!$this->usuarioActivo($idUsuario)) {
                return 1;
            }

            // Validar publicación
            $sql = "SELECT id_publicacion
                    FROM publicaciones
                    WHERE id_publicacion = ? AND bloquear = 0";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $idPublicacion);
            $stmt->execute();

            $existe = $stmt->get_result()->num_rows > 0;
            $stmt->close();

            if (!$existe) {
                return 2;
            }

            // Registrar publicación guardada
            $sql = "INSERT IGNORE INTO publicaciones_guardadas
                    (id_usuario, id_publicacion, fecha_guardado)
                    VALUES (?, ?, NOW())";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $idUsuario, $idPublicacion);
            $stmt->execute();

            $insertado = $stmt->affected_rows > 0;
            $stmt->close();

            // Si no se insertó, probablemente ya estaba guardada
            if (!$insertado) {
                return 3;
            }

            return 0;
        }


        // Quitar una publicación de guardados
        public function quitarPublicacionGuardada(int $idUsuario, int $idPublicacion): int {

            if (!$this->usuarioActivo($idUsuario)) {
                return 1;
            }

            $sql = "DELETE FROM publicaciones_guardadas
                    WHERE id_usuario = ? AND id_publicacion = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $idUsuario, $idPublicacion);
            $stmt->execute();

            $eliminado = $stmt->affected_rows > 0;
            $stmt->close();

            if (!$eliminado) {
                return 2;
            }

            return 0;
        }


        // Obtener todas las publicaciones guardadas por un usuario
        public function publicacionesGuardadas(int $idUsuario): array|false {

            if (!$this->usuarioActivo($idUsuario)) {
                return false;
            }

            $sql = "SELECT
                        p.id_publicacion,
                        p.titulo,
                        p.fecha,
                        p.mensaje,
                        p.puntos,
                        p.id_usuario,
                        p.id_materia,
                        p.estado,

                        u.nombre AS autor,
                        u.url_foto,
                        n.nombre AS nivel,
                        m.nombre AS materia,

                        pg.fecha_guardado,

                        (SELECT COUNT(*)
                        FROM likes_publicacion lp
                        WHERE lp.id_publicacion = p.id_publicacion) AS total_likes,

                        EXISTS(
                            SELECT 1
                            FROM likes_publicacion lp
                            WHERE lp.id_publicacion = p.id_publicacion
                            AND lp.id_usuario = ?
                        ) AS usuario_dio_like

                    FROM publicaciones_guardadas pg

                    INNER JOIN publicaciones p
                        ON p.id_publicacion = pg.id_publicacion

                    INNER JOIN usuarios u
                        ON u.id_usuario = p.id_usuario

                    INNER JOIN nivel_estudio n
                        ON n.id_nivel = u.id_nivel

                    INNER JOIN materias m
                        ON m.id_materia = p.id_materia

                    WHERE pg.id_usuario = ?
                    AND p.bloquear = 0
                    AND u.bloqueo_usuario = 0

                    ORDER BY pg.fecha_guardado DESC, pg.id_guardado DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $idUsuario, $idUsuario);
            $stmt->execute();

            $publicaciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            // Completar la información de cada publicación
            foreach ($publicaciones as &$publicacion) {

                $idPublicacion = (int)$publicacion['id_publicacion'];

                $publicacion['total_likes'] = (int)$publicacion['total_likes'];
                $publicacion['usuario_dio_like'] = (bool)$publicacion['usuario_dio_like'];

                // Documentos de la publicación
                $publicacion['documentos'] =
                    $this->obtenerArchivosPublicacion($idPublicacion);

                // Comentarios, respuestas, documentos y likes
                $comentarios = $this->comentariosCompletos(
                    $idPublicacion,
                    $idUsuario
                );

                $publicacion['comentarios'] =
                    $this->construirArbolComentarios($comentarios);
            }

            unset($publicacion);

            return $publicaciones;
        }


        // Obtener documentos de una publicación
        private function obtenerArchivosPublicacion(int $idPublicacion): array {

            $sql = "SELECT
                        id_archivo,
                        url_archivo,
                        nombre_archivo,
                        tipo_archivo,
                        fecha_subida
                    FROM archivos_publicaciones
                    WHERE id_publicacion = ?
                    ORDER BY id_archivo ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $idPublicacion);
            $stmt->execute();

            $archivos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            return $archivos;
        }


        // Obtener comentarios completos de una publicación
        private function comentariosCompletos(
            int $idPublicacion,
            int $idUsuario
        ): array {

            $sql = "SELECT
                        c.id_comentario,
                        c.titulo,
                        c.fecha,
                        c.mensaje,
                        c.id_usuario,
                        c.id_publicacion,
                        c.id_comentario_padre,

                        u.nombre AS autor,
                        u.url_foto,

                        (SELECT COUNT(*)
                        FROM likes_comentario lc
                        WHERE lc.id_comentario = c.id_comentario) AS total_likes,

                        EXISTS(
                            SELECT 1
                            FROM likes_comentario lc
                            WHERE lc.id_comentario = c.id_comentario
                            AND lc.id_usuario = ?
                        ) AS usuario_dio_like

                    FROM comentarios c

                    INNER JOIN usuarios u
                        ON u.id_usuario = c.id_usuario

                    WHERE c.id_publicacion = ?
                    AND c.bloquear = 0

                    ORDER BY c.fecha ASC, c.id_comentario ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $idUsuario, $idPublicacion);
            $stmt->execute();

            $comentarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            if (empty($comentarios)) {
                return [];
            }

            // Obtener los ID de los comentarios
            $ids = array_map(
                fn($comentario) => (int)$comentario['id_comentario'],
                $comentarios
            );

            // Consultar todos los archivos de los comentarios en una sola consulta
            $marcadores = implode(',', array_fill(0, count($ids), '?'));

            $sql = "SELECT
                        id_archivo,
                        id_comentario,
                        url_archivo,
                        nombre_archivo,
                        tipo_archivo,
                        fecha_subida
                    FROM archivos_comentarios
                    WHERE id_comentario IN ($marcadores)
                    ORDER BY id_archivo ASC";

            $stmt = $this->conn->prepare($sql);

            $tipos = str_repeat('i', count($ids));
            $stmt->bind_param($tipos, ...$ids);
            $stmt->execute();

            $archivos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            // Agrupar los archivos por comentario
            $archivosPorComentario = [];

            foreach ($archivos as $archivo) {
                $idComentario = (int)$archivo['id_comentario'];

                $archivosPorComentario[$idComentario][] = $archivo;
            }

            // Incorporar archivos y convertir los valores numéricos
            $idsPresentes = array_fill_keys($ids, true);

            foreach ($comentarios as &$comentario) {

                $idComentario = (int)$comentario['id_comentario'];

                $comentario['total_likes'] = (int)$comentario['total_likes'];
                $comentario['usuario_dio_like'] = (bool)$comentario['usuario_dio_like'];

                $comentario['documentos'] =
                    $archivosPorComentario[$idComentario] ?? [];

                // Si el comentario padre está bloqueado o no aparece,
                // se considera este comentario como raíz.
                if (
                    $comentario['id_comentario_padre'] !== null &&
                    !isset($idsPresentes[(int)$comentario['id_comentario_padre']])
                ) {
                    $comentario['id_comentario_padre'] = null;
                }
            }

            unset($comentario);

            return $comentarios;
        }


        // Organizar los comentarios y sus respuestas
        private function construirArbolComentarios(
            array $comentarios,
            ?int $idPadre = null
        ): array {

            $resultado = [];

            foreach ($comentarios as $comentario) {

                $padre = $comentario['id_comentario_padre'];

                if ($padre === null && $idPadre === null) {

                    $comentario['respuestas'] =
                        $this->construirArbolComentarios(
                            $comentarios,
                            (int)$comentario['id_comentario']
                        );

                    $resultado[] = $comentario;
                }
                else if (
                    $padre !== null &&
                    $idPadre !== null &&
                    (int)$padre === $idPadre
                ) {

                    $comentario['respuestas'] =
                        $this->construirArbolComentarios(
                            $comentarios,
                            (int)$comentario['id_comentario']
                        );

                    $resultado[] = $comentario;
                }
            }

            return $resultado;
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== POST ==========
        public function hacerPublicacion($titulo, $mensaje, $usuario, $materia, $archivo1, $archivo2, $archivo3, $extenciones){
            $validacion = $this->validarPOST($usuario, $materia);

            //Si pasa todas las validaciones
            if($validacion !== 0){
                return $validacion;
            }

            $archivos = [$archivo1, $archivo2, $archivo3];

            //Contamos solo los archivos realmente enviados
            $totalArchivos = 0;
            foreach ($archivos as $archivo) {
                if (is_array($archivo) && ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    $totalArchivos++;
                }
            }

            //Puntos por contenido + 10 por archivo, con tope de 100
            $puntos = $this->puntosPublicacion($titulo, $mensaje, $usuario, $materia) + ($totalArchivos * 10);
            $puntos = min($puntos, 100);

            //valores por defecto
            date_default_timezone_set('America/El_Salvador');
            $fecha = date('Y-m-d H:i:s');
            $estado = "SINRESOLVER";

            $this->conn->begin_transaction();
            $rutasSubidas = [];

            try {
                $sql = "INSERT INTO publicaciones 
                (titulo, fecha, mensaje, puntos, id_usuario, id_materia, estado) VALUES (?,?,?,?,?,?,?)";
                $stmt = $this->conn->prepare($sql);
                if ($stmt === false) {
                    throw new RuntimeException($this->conn->error);
                }
                $stmt->bind_param("sssiiis", $titulo, $fecha, $mensaje, $puntos, $usuario, $materia, $estado);
                if (!$stmt->execute()) {
                    throw new RuntimeException($stmt->error);
                }
                $idPublicacion = $this->conn->insert_id;
                $stmt->close();

                //Para cada archivo se guarda
                foreach ($archivos as $indice => $archivo) {
                    //Si no se envió un archivo
                    if (!is_array($archivo) || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }

                    //false: los puntos de los archivos ya se calcularon arriba
                    $ruta = $this->guardarDocumento($archivo, $idPublicacion, $indice + 1, false);

                    if ($ruta === false) {
                        throw new RuntimeException('Error guardando archivo ' . ($indice + 1));
                    }
                    $rutasSubidas[] = $ruta;

                    $tipoArchivo = $extenciones[$indice] ?? '';
                    $nombre = $this->nombreOriginal($archivo, $ruta);

                    $sql = "INSERT INTO archivos_publicaciones 
                    (id_publicacion, url_archivo, nombre_archivo, tipo_archivo, fecha_subida) 
                    VALUES (?,?,?,?,?)";

                    $stmt = $this->conn->prepare($sql);
                    if ($stmt === false) {
                        throw new RuntimeException($this->conn->error);
                    }
                    $stmt->bind_param("issss", $idPublicacion, $ruta, $nombre, $tipoArchivo, $fecha);
                    if (!$stmt->execute()) {
                        throw new RuntimeException($stmt->error);
                    }
                    $stmt->close();
                }

                $this->conn->commit();
                return 0;

            } catch (Throwable $e) {
                //Todo o nada: se deshace la BD y se borran los archivos ya guardados
                $this->conn->rollback();
                $this->borrarArchivosFisicos($rutasSubidas);
                error_log($e->getMessage());
                return 3;
            }
        }

        //Validaciones para POST
        public function validarPOST($usuario, $materia){

            //validar si el usaurio existe y no este bloqueado
            $sql = "SELECT id_usuario FROM usuarios WHERE id_usuario = ? AND bloqueo_usuario = 0";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $usuario); 
            $stmt->execute();
            if ($stmt->get_result()->num_rows == 0) {
                return 1;
            }

            //validar si la materia existe
            $sql = "SELECT id_materia FROM materias WHERE id_materia = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $materia); 
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                return 2;
            }

            //Si es valido
            return 0;
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== DOCUMENTOS ==========

        //Guardamos el documento en la carpeta correspondiente
        private function guardarDocumento($archivo, $idPublicacion, $numero, bool $sumarPuntos = true): string|false{
            //Obtenemos la extencion del archivo
            $extension = validarDocumento($archivo);

            //Si no regreso una extencion
            if ($extension === false) {
                return false;
            }

            $directorio = __DIR__ . '/../../../documentos/publicaciones/';
            $subdirectorio = '';

            if ($extension === "pdf") {
                $subdirectorio = 'pdf/';
            }
            else if (in_array($extension, ["jpg", "png", "webp"])) {
                $subdirectorio = 'imagenes/';
            }
            else {
                $subdirectorio = 'office/';
            }

            $directorio = __DIR__ . '/../../../documentos/publicaciones/' . $subdirectorio;

            // Crear directorio si no existe
            if (!is_dir($directorio)) {
                if (!mkdir($directorio, 0755, true) && !is_dir($directorio)) {
                    return false; // no se pudo crear el directorio
                }
            }

            $nombre = "documento_{$idPublicacion}_{$numero}_" . uniqid() . ".{$extension}";

            $rutaFisica = $directorio . $nombre;

            if (!move_uploaded_file($archivo['tmp_name'], $rutaFisica)) {
                return false;
            }

            if ($sumarPuntos) {
                $this->agregarPuntos($idPublicacion);
            }

            return "documentos/publicaciones/" . $subdirectorio . $nombre;
        }

        //Nombre original del archivo, limpio y con el largo de la columna (255)
        private function nombreOriginal($archivo, string $rutaRespaldo): string {
            $nombre = (is_array($archivo) && is_string($archivo['name'] ?? null)) ? $archivo['name'] : '';

            //Quitamos cualquier ruta y los caracteres de control
            $nombre = basename(str_replace('\\', '/', $nombre));
            $limpio = preg_replace('/[\x00-\x1F\x7F]/u', '', $nombre);
            $nombre = trim($limpio ?? '');

            //Si quedó vacío, usamos el nombre técnico
            if ($nombre === '') {
                return basename($rutaRespaldo);
            }

            //Recortamos conservando la extensión
            if (mb_strlen($nombre) > 255) {
                $extension = pathinfo($nombre, PATHINFO_EXTENSION);
                $base = pathinfo($nombre, PATHINFO_FILENAME);
                $nombre = mb_substr($base, 0, 255 - mb_strlen($extension) - 1) . '.' . $extension;
            }

            return $nombre;
        }

        // Leer rol de usuario
        private function rolDeUsuario(int $usuario): ?string {
            $sql = "SELECT r.nombre FROM usuarios u
                    INNER JOIN roles r ON r.id_rol = u.id_rol
                    WHERE u.id_usuario = ? AND u.bloqueo_usuario = 0";
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                throw new RuntimeException($this->conn->error);
            }
            $stmt->bind_param("i", $usuario);
            $stmt->execute();
            $fila = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $fila['nombre'] ?? null;
        }

        //Validar ventana de edicion
        private function dentroDeVentanaEdicion(string $fecha): bool {
            date_default_timezone_set('America/El_Salvador');
            $limite = new DateTime($fecha);
            $limite->modify('+1 hour');
            return new DateTime() < $limite;
        }

        //Autoría: 0 = permitido | 1 = publicación no existe o bloqueada | 2 = usuario inválido | 3 = sin permiso
        private function validarAutoria(int $id, int $usuario, bool $permitirAdmin): int {
            $sql = "SELECT id_usuario FROM publicaciones WHERE id_publicacion = ? AND bloquear = 0";
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                throw new RuntimeException($this->conn->error);
            }
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $fila = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$fila) {
                return 1;
            }

            $rol = $this->rolDeUsuario($usuario);
            if ($rol === null) {
                return 2;
            }

            if ((int)$fila['id_usuario'] === $usuario) {
                return 0;
            }
            if ($permitirAdmin && $rol === 'ADMINISTRADOR') {
                return 0;
            }
            return 3;
        }

        //Permisos para mostrar u ocultar botones en el detalle
        public function permisosSobre(int $id, ?int $usuario): array {
            $sin = ['puede_editar' => false, 'puede_eliminar' => false];
            if ($usuario === null) {
                return $sin;
            }

            $sql = "SELECT id_usuario, fecha FROM publicaciones WHERE id_publicacion = ? AND bloquear = 0";
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                throw new RuntimeException($this->conn->error);
            }
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $fila = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $rol = $this->rolDeUsuario($usuario);
            if (!$fila || $rol === null) {
                return $sin;
            }

            $esDueno = (int)$fila['id_usuario'] === $usuario;

            return [
                'puede_editar'   => $esDueno && $this->dentroDeVentanaEdicion($fila['fecha']),
                'puede_eliminar' => $esDueno || $rol === 'ADMINISTRADOR',
            ];
        }

        private function archivosPorPosicion($id): array {
            $stmt = $this->conn->prepare("SELECT id_archivo, url_archivo FROM archivos_publicaciones WHERE id_publicacion = ? ORDER BY id_archivo ASC");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $resultado = $stmt->get_result();

            $archivos = [];
            $posicion = 1;
            while($fila = $resultado->fetch_assoc()){
                $archivos[$posicion++] = $fila;
            }
            return $archivos;
        }

        private function borrarArchivosFisicos(array $rutas): void {
            foreach($rutas as $ruta){
                $rutaFisica = __DIR__ . '/../../../' . $ruta;
                if(is_file($rutaFisica)){
                    unlink($rutaFisica);
                }
            }
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== PUNTOS ==========
        public function puntosPublicacion($titulo, $mensaje, $usuario, $materia){
            $puntos = 10;
            //palabras claves acorde a la materia
            $sql = "SELECT palabra FROM palabras_clave WHERE id_materia = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $materia);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $palabrasClave = [];
            while ($fila = $resultado->fetch_assoc()) {
                $palabrasClave[] = mb_strtolower($fila['palabra'], 'UTF-8');
            }
            $stmt->close();

            //:::::titulo:::::
            //Si el titulo es largo, num de palabras y posee palabras relacionadas a la materia

            //longitud
            $longitudTitulo = mb_strlen($titulo, 'UTF-8');
            switch(true){
                case ($longitudTitulo > 10 && $longitudTitulo <= 50):
                    $puntos += 2;
                    break;
                case ($longitudTitulo > 50 && $longitudTitulo <= 100):
                    $puntos += 5;
                    break;
            }
            //numero de palabras
            $numPalabrasTitulo = str_word_count($titulo);
            switch(true){
                case ($numPalabrasTitulo > 10 && $numPalabrasTitulo < 20):
                    $puntos += 2;
                    break;
                case ($numPalabrasTitulo > 19 && $numPalabrasTitulo < 30):
                    $puntos += 5;
                    break;
                case ($numPalabrasTitulo > 29):
                    $puntos += 10;
                    break;
            }
            //palabras relacionadas a la materia
            $tituloNormalizado = mb_strtolower($titulo, 'UTF-8');
            $coincidenciasTitulo = 0;

            foreach ($palabrasClave as $palabra) {
                if (mb_stripos($tituloNormalizado, $palabra) !== false) {
                    $coincidenciasTitulo++;
                }
            }
            $puntos += min($coincidenciasTitulo, 5) * 2;

            //:::::mensaje:::::
            //Si el mensaje es largo y posee palabras relacionadas a la materia
            //longitud
            $longitudMensaje = mb_strlen($mensaje, 'UTF-8');
            switch(true){
                case ($longitudMensaje > 10 && $longitudMensaje < 250):
                    $puntos += 5;
                    break;
                case ($longitudMensaje > 250 && $longitudMensaje <= 500):
                    $puntos += 10;
                    break;
            }
            //palabras relacionadas a la materia
            $mensajeNormalizado = mb_strtolower($mensaje, 'UTF-8');
            $coincidenciasMensaje = 0;

            foreach ($palabrasClave as $palabra) {
                if (mb_stripos($mensajeNormalizado, $palabra) !== false) {
                    $coincidenciasMensaje++;
                }
            }
            $puntos += min($coincidenciasMensaje, 10) * 3;

            //:::::usuario:::::
            //De acuerdo al nivel de estudio del usuario y si es su primera publicación
            //nivel
            $sql = "SELECT id_nivel FROM usuarios WHERE id_usuario = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $usuario); 
            $stmt->execute();
            $filaNivel = $stmt->get_result()->fetch_assoc();
            $nivel = (int) $filaNivel['id_nivel'];
            switch(true){
                case ($nivel >= 1 && $nivel <=2):
                    $puntos += 3;
                    break;
                case ($nivel >= 3 && $nivel <=4):
                    $puntos += 3;
                    break;
                case ($nivel >= 5 && $nivel <=6):
                    $puntos += 4;
                    break;
                case ($nivel >= 7 && $nivel <=10):
                    $puntos += 4;
                    break;
                case ($nivel >= 11 && $nivel <=12):
                    $puntos += 5;
                    break;
            }
            //primera publicacion
            $sql = "SELECT id_publicacion FROM publicaciones WHERE id_usuario = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $usuario); 
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                $puntos += 5;
            }

            //:::::materia:::::
            //De acuerdo a la materia seleccionada
            switch(true){
                case ($materia >= 1 && $materia <=6):
                    $puntos += 10;
                    break;
                case ($materia >= 7 && $materia <= 14):
                    $puntos += 15;
                    break;
                case ($materia >= 15 && $materia <= 38):
                    $puntos += 20;
                    break;
            }

            if($puntos > 100){
                return 100;
            }
            else{
                return $puntos;
            }
        }

        public function agregarPuntos($idPublicacion){
            $sql = "SELECT puntos FROM publicaciones WHERE id_publicacion = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $idPublicacion); 
            $stmt->execute();
            $resultado = $stmt->get_result();
            $fila = $resultado->fetch_assoc();
            $puntos = $fila['puntos'];

            $puntos = min($puntos + 10, 100);

            $sql = "UPDATE publicaciones SET puntos = ? WHERE id_publicacion = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $puntos, $idPublicacion); 
            $stmt->execute();
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== PUT ==========
        public function actualizarPublicacion($id, $titulo, $mensaje, $materia, $archivosNuevos, $posicionesEliminar, int $usuario){
            $validacion = $this->validarPUT($id, $materia, $usuario);

            if($validacion !== 0){
                return $validacion;
            }

            // Ya no llega el usuario: se obtiene de la publicación (se necesita para calcular puntos)
            $stmt = $this->conn->prepare("SELECT id_usuario FROM publicaciones WHERE id_publicacion = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $usuario = (int) $stmt->get_result()->fetch_assoc()['id_usuario'];

            // Archivos que tiene ahora, por posición (1, 2, 3), en el mismo orden que el GET
            $archivosActuales = $this->archivosPorPosicion($id);

            //----- PASO 1: guardar los archivos nuevos en disco -----
            // Se hace primero: si algo falla, todavía no se ha borrado nada de lo anterior
            $rutasNuevas = [];
            foreach($archivosNuevos as $posicion => $archivo){
                $ruta = $this->guardarDocumento($archivo, $id, $posicion, false);

                if($ruta === false){
                    $this->borrarArchivosFisicos($rutasNuevas); // limpiamos los que ya se habian guardado
                    return 4;
                }
                $rutasNuevas[$posicion] = $ruta;
            }

            //----- PASO 2: base de datos (todo o nada) -----
            $rutasViejas = []; // archivos que se borraran del disco cuando todo salga bien
            $fecha = date('Y-m-d H:i:s');

            $this->conn->begin_transaction();
            try {
                // Datos de la publicación
                $stmt = $this->conn->prepare("UPDATE publicaciones SET titulo = ?, mensaje = ?, id_materia = ? WHERE id_publicacion = ?");
                $stmt->bind_param("ssii", $titulo, $mensaje, $materia, $id);
                if(!$stmt->execute()) throw new Exception('Error al actualizar datos');

                // Eliminar archivos pedidos (si esa posición no tiene archivo, se ignora)
                foreach($posicionesEliminar as $posicion){
                    if(!isset($archivosActuales[$posicion])) continue;

                    $stmt = $this->conn->prepare("DELETE FROM archivos_publicaciones WHERE id_archivo = ?");
                    $stmt->bind_param("i", $archivosActuales[$posicion]['id_archivo']);
                    if(!$stmt->execute()) throw new Exception('Error al eliminar archivo');

                    $rutasViejas[] = $archivosActuales[$posicion]['url_archivo'];
                }

                // Archivos nuevos: reemplazan si la posición está ocupada, se agregan si está libre
                foreach($rutasNuevas as $posicion => $ruta){
                    $nombre = $this->nombreOriginal($archivosNuevos[$posicion] ?? null, $ruta);
                    $tipo = pathinfo($ruta, PATHINFO_EXTENSION);

                    if(isset($archivosActuales[$posicion])){
                        // Reemplazo: UPDATE sobre la misma fila para que conserve su posición
                        $stmt = $this->conn->prepare("UPDATE archivos_publicaciones SET url_archivo = ?, nombre_archivo = ?, tipo_archivo = ?, fecha_subida = ? WHERE id_archivo = ?");
                        $stmt->bind_param("ssssi", $ruta, $nombre, $tipo, $fecha, $archivosActuales[$posicion]['id_archivo']);
                        $rutasViejas[] = $archivosActuales[$posicion]['url_archivo'];
                    }
                    else{
                        // Posición libre: archivo nuevo
                        $stmt = $this->conn->prepare("INSERT INTO archivos_publicaciones (id_publicacion, url_archivo, nombre_archivo, tipo_archivo, fecha_subida) VALUES (?,?,?,?,?)");
                        $stmt->bind_param("issss", $id, $ruta, $nombre, $tipo, $fecha);
                    }
                    if(!$stmt->execute()) throw new Exception('Error al guardar archivo');
                }

                // Recalcular puntos: base por contenido + 10 por cada archivo que quedó (máximo 100)
                $stmt = $this->conn->prepare("SELECT COUNT(*) FROM archivos_publicaciones WHERE id_publicacion = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $totalArchivos = (int) $stmt->get_result()->fetch_row()[0];

                $puntos = $this->puntosPublicacion($titulo, $mensaje, $usuario, $materia) + ($totalArchivos * 10);
                $puntos = min($puntos, 100);

                $stmt = $this->conn->prepare("UPDATE publicaciones SET puntos = ? WHERE id_publicacion = ?");
                $stmt->bind_param("ii", $puntos, $id);
                if(!$stmt->execute()) throw new Exception('Error al actualizar puntos');

                $this->conn->commit();
            }
            catch(Exception $e){
                // Algo falló: se deshace la BD y se borran los archivos nuevos que se habian guardado
                $this->conn->rollback();
                $this->borrarArchivosFisicos($rutasNuevas);
                return 4;
            }

            //----- PASO 3: ya todo salió bien, ahora sí se borran los archivos viejos del disco -----
            $this->borrarArchivosFisicos($rutasViejas);

            return 0;
        }

        //validaciones para PUT
        public function validarPUT($id, $materia, int $usuario){
            //Solo el dueño edita
            $autoria = $this->validarAutoria((int)$id, $usuario, false);
            if ($autoria === 1) {
                return 1;
            }
            if ($autoria === 2) {
                return 6;
            }
            if ($autoria === 3) {
                return 5;
            }

            // La materia debe existir
            $sql = "SELECT id_materia FROM materias WHERE id_materia = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $materia);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                return 2;
            }

            // Solo se puede editar durante 1 hora después de publicar
            $sql = "SELECT fecha FROM publicaciones WHERE id_publicacion = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $fila = $stmt->get_result()->fetch_assoc();
            if (!$fila || !$this->dentroDeVentanaEdicion($fila['fecha'])) {
                return 3;
            }

            return 0;
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== DELETE ==========
        public function eliminarPubliacion($id, int $usuario): int {
            $validacion = $this->validarDELETE($id, $usuario);

            //si pasa todas las validaciones
            if($validacion !== 0){
                return $validacion;
            }

            $sql = "UPDATE publicaciones SET bloquear = 1 WHERE id_publicacion = ?";
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                throw new RuntimeException($this->conn->error);
            }
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) {
                throw new RuntimeException($stmt->error);
            }
            $stmt->close();

            return 0;
        }

        //validaciones DELETE: autor/ administrador
        public function validarDELETE($id, int $usuario): int {
            return $this->validarAutoria((int)$id, $usuario, true);
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== PATCH ==========
        public function cambiarEstadoPublicacion($id, $estado, int $usuario): int {
            $validacion = $this->validarPATCH($id, $usuario);

            //si pasa todas las validaciones
            if($validacion !== 0){
                return $validacion;
            }

            $sql = "UPDATE publicaciones SET estado = ? WHERE id_publicacion = ?";
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                throw new RuntimeException($this->conn->error);
            }
            $stmt->bind_param("si", $estado, $id);
            if (!$stmt->execute()) {
                throw new RuntimeException($stmt->error);
            }
            $stmt->close();

            return 0;
        }

        //validaciones PATCH: autor / administrador
        public function validarPATCH($id, int $usuario): int {
            return $this->validarAutoria((int)$id, $usuario, true);
        }

    }
?>