<?php 
    class Usuario {
        private mysqli $conn;

        //Ajusta este ID al rol de administrador de tu tabla `roles` (el mismo de Profesor::ROL_ADMIN)
        private const ROL_ADMIN = 3;
        
        public function __construct($dbConnection){
            $this->conn = $dbConnection;
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== GET ==========

        public function listarUsuarios(){
            $sql = "SELECT nombre, correo, id_rol, fecha_registro, id_nivel, url_foto FROM usuarios WHERE bloqueo_usuario = 0";
            $usuarios = $this->conn->query($sql);
            return $usuarios->fetch_all(MYSQLI_ASSOC);
        }

        //---Busqueda por ID---
        public function buscarPorID($id){
            $sql = ("SELECT u.nombre, u.correo, u.id_rol, r.nombre AS rol, u.fecha_registro, n.nombre AS nivel, url_foto  FROM usuarios u
            JOIN  nivel_estudio n
            ON u.id_nivel = n.id_nivel
            JOIN roles r
            ON u.id_rol = r.id_rol
            WHERE u.id_usuario = ? AND u.bloqueo_usuario = 0");
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id); 
            
            if($stmt->execute()){
                return $stmt->get_result()->fetch_assoc(); 
            }
        }

        //---Busqueda mediante filtros---
        public function buscarPorFiltros($busqueda, $correo, $nivel, $orden, $bloqueado){
            $sql = ("SELECT nombre, correo, id_rol, fecha_registro, id_nivel, url_foto  FROM usuarios WHERE 1=1");
            $parametros = [];
            $tipoParametro = "";

            //busqueda por estado de bloqueo
            if ($bloqueado !== ''){
                $sql .= " AND bloqueo_usuario = ?";
                $parametros[] = $bloqueado;
                $tipoParametro .= "i";
            }

            //busqueda por nombre
            if ($busqueda !== ''){
                $sql .= " AND nombre LIKE ? ";
                $parametros[] = "%".$busqueda."%";
                $tipoParametro .= "s";
            }

            //busqueda por correo
            if ($correo !== ''){
                $sql .= " AND correo = ?";
                $parametros[] = $correo;
                $tipoParametro .= "s";
            }

            //busqueda por nivel educativo
            if ($nivel !== ''){
                $sql .= " AND id_nivel = ?";
                $parametros[] = $nivel;
                $tipoParametro .= "s";
            }

            //ordenar de forma
            if ($orden === 'ASC'){
                $sql .= " ORDER BY nombre ASC";
            }
            if($orden === 'DESC'){
                $sql .= " ORDER BY nombre DESC";
            }

            //ejecutamos el script SQL
            $stmt = $this->conn->prepare($sql);
            if (!empty($parametros)) {
                $stmt->bind_param($tipoParametro, ...$parametros);
            }
            $stmt->execute();

            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        //---Administracion: indica si el usuario es administrador (lee la base, no la sesion)---
        public function esAdministrador($id): bool{
            $sql = "SELECT id_usuario FROM usuarios WHERE id_usuario = ? AND id_rol = ? AND bloqueo_usuario = 0";
            $stmt = $this->conn->prepare($sql);
            $rolAdmin = self::ROL_ADMIN;
            $stmt->bind_param("ii", $id, $rolAdmin);
            $stmt->execute();
            return $stmt->get_result()->num_rows > 0;
        }

        //---Administracion: roles disponibles---
        public function listarRoles(){
            $resultado = $this->conn->query("SELECT id_rol, nombre FROM roles ORDER BY id_rol ASC");
            return $resultado->fetch_all(MYSQLI_ASSOC);
        }

        //---Administracion: listado con id, rol, nivel y estado de bloqueo (incluye bloqueados)---
        public function buscarParaAdministracion($busqueda, $rol, $bloqueado, $orden){
            $sql = "SELECT u.id_usuario, u.nombre, u.correo, u.id_rol, r.nombre AS rol,
                u.id_nivel, n.nombre AS nivel, u.fecha_registro, u.url_foto, u.bloqueo_usuario
            FROM usuarios u
            JOIN roles r ON r.id_rol = u.id_rol
            LEFT JOIN nivel_estudio n ON n.id_nivel = u.id_nivel
            WHERE 1=1";
            $parametros = [];
            $tipoParametro = "";

            //busqueda por nombre o correo
            if ($busqueda !== ''){
                $sql .= " AND (u.nombre LIKE ? OR u.correo LIKE ?)";
                $like = "%".$busqueda."%";
                array_push($parametros, $like, $like);
                $tipoParametro .= "ss";
            }

            //filtro por rol
            if ($rol !== ''){
                $sql .= " AND u.id_rol = ?";
                $parametros[] = (int)$rol;
                $tipoParametro .= "i";
            }

            //filtro por bloqueo
            if ($bloqueado !== ''){
                $sql .= " AND u.bloqueo_usuario = ?";
                $parametros[] = (int)$bloqueado;
                $tipoParametro .= "i";
            }

            //orden
            if ($orden === 'ASC'){
                $sql .= " ORDER BY u.nombre ASC";
            }
            else if ($orden === 'DESC'){
                $sql .= " ORDER BY u.nombre DESC";
            }
            else{
                $sql .= " ORDER BY u.fecha_registro DESC";
            }

            $stmt = $this->conn->prepare($sql);
            if (!empty($parametros)) {
                $stmt->bind_param($tipoParametro, ...$parametros);
            }
            $stmt->execute();

            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        //Obtener usuario por correo para el login
        public function consultarUsuario($correo){
            $sql = "SELECT u.id_usuario, u.nombre, u.contrasena, u.bloqueo_usuario, r.nombre AS rol
                    FROM usuarios u
                    INNER JOIN roles r ON r.id_rol = u.id_rol
                    WHERE u.correo = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $correo);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc() ?: null;
        }

        //Usuario por id para la sesión
        public function obtenerUsuarioPorId($id){
            $sql = "SELECT u.id_usuario, u.nombre, u.correo, u.id_nivel, u.url_foto, r.nombre AS rol
                    FROM usuarios u
                    INNER JOIN roles r ON r.id_rol = u.id_rol
                    WHERE u.id_usuario = ? AND u.bloqueo_usuario = 0";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc() ?: null;
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== POST ==========
        public function agregarUsuario($nombre, $correo, $contrasena, $fecha, $nivel){
            $validacion = $this->validarPOST($correo, $nivel);

            //Si pasa todas las validaciones
            if($validacion !== 0){
                return $validacion;
            }

            //El rol por defecto
            $stmt = $this->conn->prepare("SELECT id_rol FROM roles WHERE nombre = 'ESTUDIANTE'");
            $stmt->execute();
            $fila = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if(!$fila){
                return 3;
            }
            $rol = (int)$fila['id_rol'];

            $hash = password_hash($contrasena, PASSWORD_DEFAULT);

            $sql = "INSERT INTO usuarios
            (nombre, correo, contrasena, id_rol, fecha_registro, id_nivel) VALUES (?,?,?,?,?,?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sssisi", $nombre, $correo, $hash, $rol, $fecha, $nivel);
            $stmt->execute();
            return 0;
        }

        //Validaciones para POST
        public function validarPOST($correo, $nivel){

            //validar si el correo ya esta en uso
            $sql = "SELECT id_usuario FROM usuarios WHERE correo = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $correo); 
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                return 1;
            }

            //validar si el nivel de estudio se encuentra en la base de datos
            $sql = "SELECT id_nivel FROM nivel_estudio WHERE id_nivel = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $nivel); 
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                return 2;
            }

            //Si es valido
            return 0;
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== CORREO DE RECUPERACION ==========

        public function recuperarContrasena($correo){
            $validacion = $this->validarRecuperar($correo);

            //Si pasa todas las validaciones
            if($validacion === false){
                return 1;
            }
            //Valores por defecto
            date_default_timezone_set('America/El_Salvador');
            $expiracion = new DateTime('now', new DateTimeZone('America/El_Salvador'));
            $expiracion->modify('+30 minutes');
            $fecha = $expiracion->format('Y-m-d H:i:s');

            $token   = bin2hex(random_bytes(32));

            $sql = "INSERT INTO token_reseteo 
            (id_usuario, token, fecha_expiracion) VALUES (?,?,?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iss", $validacion, $token, $fecha);
            $stmt->execute();

            return 0;
        }

        private function validarRecuperar($correo){
            //validar si el correo existe y elñ usuario no este bloqueado
            $sql = "SELECT id_usuario FROM usuarios WHERE correo = ? AND bloqueo_usuario = 0";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $correo); 
            $stmt->execute();
            $resultado = $stmt->get_result();

            if ($resultado->num_rows === 0) {
                return false;
            }

            $usuario = $resultado->fetch_assoc();
            return $usuario['id_usuario'];
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== CAMBIAR CONTRASEÑA ==========
        public function cambiarContrasena($contrasena, $token){
            
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== IMAGEN DE PERFIL ==========
        // Raíz del proyecto (ajusta los niveles según donde esté este archivo)
        private const RUTA_DOCUMENTOS = __DIR__ . '/../../../documentos/usuarios/';

        public function guardarImagenPerfil($imagen, $idUsuario){
            $extension = validarDocumento($imagen);
            if ($extension === false || !in_array($extension, ["jpg", "png", "webp"], true)) {
                return false;
            }

            $subdirectorio = 'imagenes/';
            $directorio = self::RUTA_DOCUMENTOS . $subdirectorio;

            // Crear directorio si no existe
            if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) {
                error_log("No se pudo crear el directorio: $directorio");
                return false;
            }

            $nombre = "imagen_perfil_usuario_{$idUsuario}_" . uniqid() . ".{$extension}";
            $rutaFisica = $directorio . $nombre;

            error_log("DEBUG dir real: " . realpath($directorio));
error_log("DEBUG existe: " . var_export(is_dir($directorio), true));
error_log("DEBUG escribible: " . var_export(is_writable($directorio), true));
error_log("DEBUG tmp existe: " . var_export(is_uploaded_file($imagen['tmp_name']), true));
            if (!move_uploaded_file($imagen['tmp_name'], $rutaFisica)) {
                error_log("move_uploaded_file falló hacia: $rutaFisica");
                return false;
            }

            return "documentos/usuarios/" . $subdirectorio . $nombre;
        }
        public function cambiarImagenPerfil($idUsuario, $imagen){
            $validacion = $this->validarCambioImagen($idUsuario);

            if(!$validacion){
                return 1;
            }

            $ruta = $this->guardarImagenPerfil($imagen, $idUsuario);

            if($ruta === false) {
                return 2;
            }

            $sql = "UPDATE usuarios SET url_foto = ? WHERE id_usuario = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $ruta, $idUsuario); 
            $stmt->execute();

            return 0;
        }

        public function validarCambioImagen($usuario){
            //validar si el usaurio existe y no este bloqueado
            $sql = "SELECT id_usuario FROM usuarios WHERE id_usuario = ? AND bloqueo_usuario = 0";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $usuario); 
            $stmt->execute();
            if ($stmt->get_result()->num_rows == 0) {
                return false;
            }
            return true;
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== PUT ==========
        public function actualizarUsuario($id, $nombre, $correo, $contrasena_anterior,$contrasena, $nivel){
            $validacion = $this->validarPUT($id, $contrasena_anterior, $contrasena, $correo, $nivel);
            
            //si pasa todas las validaciones
            if($validacion !== 0){
                return $validacion;
            }

            //hasheo de contraseña nueva
            $hash = $contrasena !== null
                ? password_hash($contrasena, PASSWORD_DEFAULT)
                : null;

            $sql = "UPDATE usuarios SET
                nombre = COALESCE(?, nombre),
                correo = COALESCE(?, correo),
                contrasena = COALESCE(?, contrasena),
                id_nivel = COALESCE(?, id_nivel)
            WHERE id_usuario = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sssii", $nombre, $correo, $hash, $nivel, $id); 
            $stmt->execute();

            return 0;
        }

        //validaciones para PUT
        public function validarPUT($id, $contrasenaAnterior, $contrasena, $correo, $nivel){
            //validar si la id existe en la base de datos la ID
            $sql = "SELECT contrasena FROM usuarios WHERE id_usuario = ? AND bloqueo_usuario = 0";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $resultado = $stmt->get_result();
            if($resultado->num_rows === 0){
                return 1;
            }

            $usuario = $resultado->fetch_assoc();

            //validar si la contraseña anterior coincide
            if($contrasena !== null && $contrasena !== ''){
                if($contrasenaAnterior === null || $contrasenaAnterior === ''){
                    return 2;
                }
                if(!password_verify($contrasenaAnterior, $usuario['contrasena'])){
                    return 2;
                }
            }

            //validar si el correo ya esta siendo usado por otro usuario
            if($correo !== null){
                $sql = "SELECT id_usuario FROM usuarios WHERE correo = ? AND id_usuario != ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("si", $correo, $id);
                $stmt->execute();

                if($stmt->get_result()->num_rows > 0){
                    return 3;
                }
            }
            
            //validar si el nivel de estudio se encuentra en la base de datos
            if($nivel !== null){
                $sql = "SELECT id_nivel FROM nivel_estudio WHERE id_nivel = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("i", $nivel);
                $stmt->execute();

                if($stmt->get_result()->num_rows === 0){
                    return 4;
                }
            }
            return 0;
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== DELETE ==========
        public function eliminarUsuario($id){
            $validacion = $this->validarDELETE($id);
            
            //si pasa todas las validaciones
            if($validacion !== true){
                return $validacion;
            }

            $sql = "UPDATE usuarios SET bloqueo_usuario = 1 WHERE id_usuario = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id); 
            $stmt->execute();

            return true;
        }

        //validaciones DELETE
        public function validarDELETE($id){
            $sql = "SELECT id_usuario FROM usuarios
            WHERE id_usuario = ? AND bloqueo_usuario = 0";
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
        public function cambiarRolUsuario($id, $rol){
            $validacion = $this->validarPATCH($id, $rol);
            
            //si pasa todas las validaciones
            if($validacion !== 0){
                return $validacion;
            }

            $sql = "UPDATE usuarios SET id_rol = ? WHERE id_usuario = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $rol, $id); 
            $stmt->execute();

            return 0;
        }

        //---Bloquear (1) o desbloquear (0) un usuario---
        // 0 = ok | 1 = usuario no encontrado
        public function cambiarBloqueo($id, $bloqueo){
            $stmt = $this->conn->prepare("SELECT id_usuario FROM usuarios WHERE id_usuario = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                return 1;
            }

            $stmt = $this->conn->prepare("UPDATE usuarios SET bloqueo_usuario = ? WHERE id_usuario = ?");
            $stmt->bind_param("ii", $bloqueo, $id);
            $stmt->execute();

            return 0;
        }

        //validaciones PATCH
        public function validarPATCH($id, $rol){
            //validar si la id existe en la base de datos la ID
            $sql = "SELECT * FROM usuarios WHERE id_usuario = ? AND bloqueo_usuario = 0";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id); 
            $stmt->execute();
            if ($stmt->get_result()->num_rows == 0) {
                return 1;
            }

            //validar si el rol existe
            $sql = "SELECT * FROM roles WHERE id_rol = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $rol); 
            $stmt->execute();
            if ($stmt->get_result()->num_rows == 0) {
                return 2;
            }

            return 0;
        }

    }
?>