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

            (SELECT a.url_archivo FROM archivos_publicaciones a WHERE a.id_publicacion = p.id_publicacion ORDER BY a.id_archivo ASC LIMIT 1 OFFSET 2) AS archivo_3

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
        //========== POST ==========
        public function hacerPublicacion($titulo, $mensaje, $usuario, $materia, $archivo1, $archivo2, $archivo3, $extenciones){
            $validacion = $this->validarPOST($usuario, $materia);

            //Si pasa todas las validaciones
            if($validacion !== 0){
                return $validacion;
            }

            //Calculamos los puntos de la publicación a partir de los datos ingresados
            $puntos = $this->puntosPublicacion($titulo, $mensaje, $usuario, $materia);

            //valores por defecto
            date_default_timezone_set('America/El_Salvador');
            $fecha = date('Y-m-d H:i:s');
            $estado = "SINRESOLVER";
            
            $sql = "INSERT INTO publicaciones 
            (titulo, fecha, mensaje, puntos, id_usuario, id_materia, estado) VALUES (?,?,?,?,?,?,?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sssiiis", $titulo, $fecha, $mensaje, $puntos, $usuario, $materia, $estado);
            $stmt->execute();
            $idPublicacion = $this->conn->insert_id;

            $archivos = [$archivo1, $archivo2, $archivo3];

            //Para cada archivo se guarda
            foreach ($archivos as $indice => $archivo) {
                //Si no se envió un archivo
                if ($archivo === null || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $ruta = $this->guardarDocumento($archivo, $idPublicacion, $indice + 1);

                if ($ruta === false) {
                    return 3;
                }

                $tipoArchivo = $extenciones[$indice];
                $nombre = basename($ruta);

                $sql = "INSERT INTO archivos_publicaciones 
                (id_publicacion, url_archivo, nombre_archivo, tipo_archivo, fecha_subida) 
                VALUES (?,?,?,?,?)";

                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("issss", $idPublicacion, $ruta, $nombre, $tipoArchivo, $fecha);

                if (!$stmt->execute()) {
                    return 3;
                }
            }

            return 0;
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

            // Crear directorio si no existe...

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

            $puntos = $puntos + 10;

            $sql = "UPDATE publicaciones SET puntos = ? WHERE id_publicacion = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $puntos, $idPublicacion); 
            $stmt->execute();
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== PUT ==========
        public function actualizarPublicacion($id, $titulo, $mensaje, $materia, $archivosNuevos, $posicionesEliminar){
            $validacion = $this->validarPUT($id, $materia);

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
                    $nombre = basename($ruta);
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
        public function validarPUT($id, $materia){
            //validar si la id existe en la base de datos la ID
           // La publicación debe existir y no estar bloqueada (y de paso traemos su fecha)
            $sql = "SELECT fecha FROM publicaciones WHERE id_publicacion = ? AND bloquear = 0";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $fila = $stmt->get_result()->fetch_assoc();
            if (!$fila) {
                return 1;
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
            date_default_timezone_set('America/El_Salvador');
            $limite = new DateTime($fila['fecha']);
            $limite->modify('+1 hour');
            if (new DateTime() >= $limite) {
                return 3;
            }

            return 0;
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== DELETE ==========
        public function eliminarPubliacion($id){
            $validacion = $this->validarDELETE($id);
            
            //si pasa todas las validaciones
            if($validacion !== true){
                return $validacion;
            }

            $sql = "UPDATE publicaciones SET bloquear = 1 WHERE id_publicacion = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id); 
            $stmt->execute();

            return true;
        }

        //validaciones DELETE
        public function validarDELETE($id){
            $sql = "SELECT id_publicacion FROM publicaciones
            WHERE id_publicacion = ? AND bloquear = 0";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                return false;
            }
            return true;
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== PATCH ==========
        public function cambiarEstadoPublicacion($id, $estado){
            $validacion = $this->validarPATCH($id);
            
            //si pasa todas las validaciones
            if($validacion !== true){
                return $validacion;
            }

            $sql = "UPDATE publicaciones SET estado = ? WHERE id_publicacion = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $estado, $id); 
            $stmt->execute();

            return true;
        }

        //validaciones PATCH
        public function validarPATCH($id){
            //validar si la id existe en la base de datos la ID
            $sql = "SELECT id_publicacion FROM publicaciones WHERE id_publicacion = ? AND bloquear = 0";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id); 
            $stmt->execute();
            if ($stmt->get_result()->num_rows == 0) {
                return false;
            }

            return true;
        }

    }
?>