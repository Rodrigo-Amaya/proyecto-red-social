<?php 
    require_once __DIR__ . '/../../ModuloNotificaciones/Model/Notificacion.php';

    class Profesor {
        private mysqli $conn;

        //Ajusta estos IDs a los que tengas en la tabla `roles`
        private const ROL_PROFESOR = 2;
        private const ROL_ADMIN = 3;

        //Dias que se dan para cada etapa de revisión
        private const DIAS_REVISION = 3;

        private const EXTENSIONES_TITULO = ["pdf", "jpg", "jpeg", "png", "webp"];

        public function __construct($dbConnection){
            $this->conn = $dbConnection;
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== GET ==========

        public function listarAplicaciones(){
            $sql = "SELECT a.id_aplicacion, a.id_usuario, u.nombre AS nombre_usuario, u.correo,
                a.universidad, a.telefono_universidad, a.carrera, a.grado_academico, a.anio_graducacion,
                a.url_titulo, a.estado, a.fecha_aplicacion, a.primera_revision_fecha, a.segunda_revision_fecha, a.razon_rechazo
            FROM aplicacion_profesor a
            JOIN usuarios u ON a.id_usuario = u.id_usuario
            ORDER BY a.fecha_aplicacion DESC";
            $aplicaciones = $this->conn->query($sql);
            return $aplicaciones->fetch_all(MYSQLI_ASSOC);
        }

        //---Busqueda por ID (incluye el historial de revisiones)---
        public function obtenerAplicacion($id){
            $sql = "SELECT a.id_aplicacion, a.id_usuario, u.nombre AS nombre_usuario, u.correo,
                a.universidad, a.telefono_universidad, a.carrera, a.grado_academico, a.anio_graducacion,
                a.url_titulo, a.estado, a.fecha_aplicacion, a.primera_revision_fecha, a.segunda_revision_fecha, a.razon_rechazo
            FROM aplicacion_profesor a
            JOIN usuarios u ON a.id_usuario = u.id_usuario
            WHERE a.id_aplicacion = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id); 
            $stmt->execute();
            $aplicacion = $stmt->get_result()->fetch_assoc();

            if(!$aplicacion){
                return null;
            }

            $sql = "SELECT r.etapa_revision AS etapa, r.decision, r.comentario, r.fecha, u.nombre AS administrador
            FROM revision_aplicacion_profesor r
            JOIN usuarios u ON r.id_administrador = u.id_usuario
            WHERE r.id_aplicacion = ?
            ORDER BY r.fecha ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $aplicacion['revisiones'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            return $aplicacion;
        }

        //---Busqueda mediante filtros---
        public function buscarPorFiltros($busqueda, $estado, $usuario, $orden){
            $sql = "SELECT a.id_aplicacion, a.id_usuario, u.nombre AS nombre_usuario, u.correo,
                a.universidad, a.carrera, a.grado_academico, a.anio_graducacion,
                a.estado, a.fecha_aplicacion, a.primera_revision_fecha, a.segunda_revision_fecha
            FROM aplicacion_profesor a
            JOIN usuarios u ON a.id_usuario = u.id_usuario
            WHERE 1=1";
            $parametros = [];
            $tipoParametro = "";

            //busqueda por universidad, carrera o nombre del usuario
            if ($busqueda !== ''){
                $sql .= " AND (a.universidad LIKE ? OR a.carrera LIKE ? OR u.nombre LIKE ?)";
                $like = "%".$busqueda."%";
                array_push($parametros, $like, $like, $like);
                $tipoParametro .= "sss";
            }

            //busqueda por estado
            if ($estado !== ''){
                $sql .= " AND a.estado = ?";
                $parametros[] = $estado;
                $tipoParametro .= "s";
            }

            //busqueda por usuario
            if ($usuario !== ''){
                $sql .= " AND a.id_usuario = ?";
                $parametros[] = $usuario;
                $tipoParametro .= "i";
            }

            //ordenar por fecha de aplicacion
            if ($orden === 'ASC'){
                $sql .= " ORDER BY a.fecha_aplicacion ASC";
            }
            else{
                $sql .= " ORDER BY a.fecha_aplicacion DESC";
            }

            $stmt = $this->conn->prepare($sql);
            if (!empty($parametros)) {
                $stmt->bind_param($tipoParametro, ...$parametros);
            }
            $stmt->execute();

            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== POST ==========
        // 0 = ok | 1 = usuario no existe/bloqueado | 2 = ya es profesor | 3 = ya tiene una aplicacion activa | 4 = error al guardar el titulo
        public function aplicar($usuario, $universidad, $telefono, $carrera, $grado, $anio, $archivo){
            $validacion = $this->validarPOST($usuario);

            //Si pasa todas las validaciones
            if($validacion !== 0){
                return $validacion;
            }

            $ruta = $this->guardarTitulo($archivo, $usuario);
            if($ruta === false){
                return 4;
            }

            //Fecha de la primera revision
            date_default_timezone_set('America/El_Salvador');
            $primeraRevision = (new DateTime('now', new DateTimeZone('America/El_Salvador')))
                ->modify('+' . self::DIAS_REVISION . ' days')
                ->format('Y-m-d H:i:s');

            try {
                $sql = "INSERT INTO aplicacion_profesor
                (id_usuario, universidad, telefono_universidad, carrera, grado_academico, anio_graducacion, url_titulo, primera_revision_fecha)
                VALUES (?,?,?,?,?,?,?,?)";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("issssiss", $usuario, $universidad, $telefono, $carrera, $grado, $anio, $ruta, $primeraRevision);
                $stmt->execute();
                $idAplicacion = $this->conn->insert_id;
            } catch (mysqli_sql_exception $e) {
                $this->borrarArchivoFisico($ruta);
                return 4;
            }

            //Avisar a los administradores (si falla, la aplicacion ya quedo guardada)
            try {
                (new Notificacion($this->conn))->notificarNuevaSolicitud((int)$idAplicacion, (int)$usuario);
            } catch (Throwable $e) {
                error_log('No se pudo crear la notificación de solicitud: ' . $e->getMessage());
            }

            return 0;
        }

        //Validaciones para POST
        public function validarPOST($usuario){
            //validar si el usuario existe y no esta bloqueado
            $sql = "SELECT id_rol FROM usuarios WHERE id_usuario = ? AND bloqueo_usuario = 0";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $usuario);
            $stmt->execute();
            $resultado = $stmt->get_result();
            if ($resultado->num_rows === 0) {
                return 1;
            }

            //validar que no sea ya profesor
            if ((int)$resultado->fetch_assoc()['id_rol'] === self::ROL_PROFESOR) {
                return 2;
            }

            //validar que no tenga otra aplicacion en curso
            $sql = "SELECT id_aplicacion FROM aplicacion_profesor
            WHERE id_usuario = ? AND estado IN ('PENDIENTE', 'EN_SEGUNDA_REVISION')";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $usuario);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                return 3;
            }

            return 0;
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== DOCUMENTOS ==========

        //Guardamos el titulo en la carpeta correspondiente
        private function guardarTitulo($archivo, $idUsuario): string|false{
            $extension = validarDocumento($archivo);

            //Si no regreso una extencion
            if ($extension === false) {
                return false;
            }

            $extension = strtolower($extension);
            if (!in_array($extension, self::EXTENSIONES_TITULO, true)) {
                return false;
            }

            $subdirectorio = $extension === "pdf" ? 'pdf/' : 'imagenes/';
            $directorio = __DIR__ . '/../../../documentos/profesores/' . $subdirectorio;

            if (!is_dir($directorio) && !mkdir($directorio, 0775, true)) {
                return false;
            }

            $nombre = "titulo_usuario_{$idUsuario}_" . uniqid() . ".{$extension}";

            if (!move_uploaded_file($archivo['tmp_name'], $directorio . $nombre)) {
                return false;
            }

            return "documentos/profesores/" . $subdirectorio . $nombre;
        }

        private function borrarArchivoFisico(?string $ruta): void {
            if($ruta === null || $ruta === ''){
                return;
            }
            $rutaFisica = __DIR__ . '/../../../' . $ruta;
            if(is_file($rutaFisica)){
                unlink($rutaFisica);
            }
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== PUT ==========
        // 0 = ok | 1 = aplicacion no encontrada | 2 = ya no se puede modificar | 3 = error al guardar el titulo
        // $archivo = null mantiene el titulo actual
        public function actualizarAplicacion($id, $universidad, $telefono, $carrera, $grado, $anio, $archivo){
            $aplicacion = $this->validarPUT($id);

            //si pasa todas las validaciones
            if(is_int($aplicacion)){
                return $aplicacion;
            }

            $rutaNueva = null;
            if($archivo !== null){
                $rutaNueva = $this->guardarTitulo($archivo, $aplicacion['id_usuario']);
                if($rutaNueva === false){
                    return 3;
                }
            }

            try {
                $sql = "UPDATE aplicacion_profesor SET
                    universidad = ?,
                    telefono_universidad = ?,
                    carrera = ?,
                    grado_academico = ?,
                    anio_graducacion = ?,
                    url_titulo = COALESCE(?, url_titulo)
                WHERE id_aplicacion = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("ssssisi", $universidad, $telefono, $carrera, $grado, $anio, $rutaNueva, $id);
                $stmt->execute();
            } catch (mysqli_sql_exception $e) {
                $this->borrarArchivoFisico($rutaNueva);
                return 3;
            }

            //Borramos el titulo anterior solo si se reemplazo
            if($rutaNueva !== null){
                $this->borrarArchivoFisico($aplicacion['url_titulo']);
            }

            return 0;
        }

        //validaciones para PUT: devuelve la aplicacion o un codigo de error
        public function validarPUT($id){
            $sql = "SELECT id_usuario, estado, url_titulo FROM aplicacion_profesor WHERE id_aplicacion = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $aplicacion = $stmt->get_result()->fetch_assoc();

            if(!$aplicacion){
                return 1;
            }

            //Solo se puede corregir mientras no haya empezado la revision
            if($aplicacion['estado'] !== 'PENDIENTE'){
                return 2;
            }

            return $aplicacion;
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== DELETE ==========
        //No se borra el registro, se marca como CANCELADA
        public function cancelarAplicacion($id){
            $validacion = $this->validarDELETE($id);

            //si pasa todas las validaciones
            if($validacion !== true){
                return $validacion;
            }

            $sql = "UPDATE aplicacion_profesor SET estado = 'CANCELADA' WHERE id_aplicacion = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id); 
            $stmt->execute();

            //Ya no hay nada que revisar: se retira el aviso de los administradores
            try {
                (new Notificacion($this->conn))->retirarNuevaSolicitud((int)$id);
            } catch (Throwable $e) {
                error_log('No se pudo retirar la notificación de solicitud: ' . $e->getMessage());
            }

            return true;
        }

        //validaciones DELETE
        public function validarDELETE($id){
            $sql = "SELECT id_aplicacion FROM aplicacion_profesor
            WHERE id_aplicacion = ? AND estado IN ('PENDIENTE', 'EN_SEGUNDA_REVISION')";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                return false;
            }
            return true;
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== PATCH (REVISION DEL ADMINISTRADOR) ==========
        // 0 = ok | 1 = no es administrador | 2 = aplicacion no encontrada | 3 = aplicacion no esta en revision | 4 = error al guardar
        public function revisarAplicacion($id, $idAdministrador, $decision, $comentario){
            $aplicacion = $this->validarPATCH($id, $idAdministrador);

            //si pasa todas las validaciones
            if(is_int($aplicacion)){
                return $aplicacion;
            }

            //Etapa segun el estado actual
            $etapa = $aplicacion['estado'] === 'PENDIENTE' ? 1 : 2;

            date_default_timezone_set('America/El_Salvador');
            $segundaRevision = null;
            $razon = null;

            if($decision === 'RECHAZADA'){
                $nuevoEstado = 'RECHAZADA';
                $razon = $comentario;
            }
            else if($etapa === 1){
                $nuevoEstado = 'EN_SEGUNDA_REVISION';
                $segundaRevision = (new DateTime('now', new DateTimeZone('America/El_Salvador')))
                    ->modify('+' . self::DIAS_REVISION . ' days')
                    ->format('Y-m-d H:i:s');
            }
            else{
                $nuevoEstado = 'APROBADA';
            }

            $this->conn->begin_transaction();
            try {
                //Guardamos la revision
                $sql = "INSERT INTO revision_aplicacion_profesor
                (id_aplicacion, id_administrador, etapa_revision, decision, comentario) VALUES (?,?,?,?,?)";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("iiiss", $id, $idAdministrador, $etapa, $decision, $comentario);
                $stmt->execute();

                
                //Actualizamos la aplicacion
                $sql = "UPDATE aplicacion_profesor SET
                    estado = ?,
                    segunda_revision_fecha = COALESCE(?, segunda_revision_fecha),
                    razon_rechazo = COALESCE(?, razon_rechazo)
                WHERE id_aplicacion = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("sssi", $nuevoEstado, $segundaRevision, $razon, $id);
                $stmt->execute();

                //Si fue aprobada en la segunda etapa, el usuario pasa a ser profesor
                if($nuevoEstado === 'APROBADA'){
                    $rol = self::ROL_PROFESOR;
                    $sql = "UPDATE usuarios SET id_rol = ? WHERE id_usuario = ?";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->bind_param("ii", $rol, $aplicacion['id_usuario']);
                    $stmt->execute();
                }

                $this->conn->commit();
            } catch (mysqli_sql_exception $e) {
                $this->conn->rollback();
                return 4;
            }

            return 0;
        }

        //validaciones PATCH: devuelve la aplicacion o un codigo de error
        public function validarPATCH($id, $idAdministrador){
            //validar que quien revisa sea administrador activo
            $sql = "SELECT id_usuario FROM usuarios WHERE id_usuario = ? AND id_rol = ? AND bloqueo_usuario = 0";
            $stmt = $this->conn->prepare($sql);
            $rolAdmin = self::ROL_ADMIN;
            $stmt->bind_param("ii", $idAdministrador, $rolAdmin);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                return 1;
            }

            //validar que la aplicacion exista
            $sql = "SELECT id_usuario, estado FROM aplicacion_profesor WHERE id_aplicacion = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $aplicacion = $stmt->get_result()->fetch_assoc();
            if(!$aplicacion){
                return 2;
            }

            //validar que este en una etapa revisable
            if(!in_array($aplicacion['estado'], ['PENDIENTE', 'EN_SEGUNDA_REVISION'], true)){
                return 3;
            }

            return $aplicacion;
        }
    }
?>