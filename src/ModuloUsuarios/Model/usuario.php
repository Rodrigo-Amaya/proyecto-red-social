<?php 
    class Usuario {
        private mysqli $conn;
        
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
            $sql = ("SELECT nombre, correo, id_rol, fecha_registro, id_nivel, url_foto  FROM usuarios WHERE id_usuario = ? AND bloqueo_usuario = 0");
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

        //Verificar usuario
        public function verficarUsuario($correo, $contrasena){
            $sql = ("SELECT id_usuario, nombre, id_rol, correo, contrasena, bloqueo_usuario FROM usuarios WHERE correo = ?");
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $correo); 
            
            if($stmt->execute()){
                $resultado = $stmt->get_result();
                $usuario = $resultado->fetch_assoc();

                if(!$usuario){
                    $stmt->close();
                    return ['estado' => 1];
                }

                $bloqueo = $usuario['bloqueo_usuario'];
                if($bloqueo){
                    return ['estado' => 2];
                }

                $contrasenaSQL = $usuario['contrasena'];

                if(!password_verify($contrasena, $contrasenaSQL)){
                    return ['estado' => 3];
                }

                return ['estado' => 0, 'usuario' => $usuario];
            }
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== POST ==========
        public function agregarUsuario($nombre, $correo, $contrasena, $fecha, $nivel){
            $validacion = $this->validarPOST($correo, $nivel);

            //Si pasa todas las validaciones
            if($validacion !== 0){
                return $validacion;
            }

            //Valores por defecto y cambio de valores
            $rol = 1;
            $hash = password_hash($contrasena, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO usuarios 
            (nombre, correo, contrasena, id_rol, fecha_registro, id_nivel) VALUES (?,?,?,?,?,?,?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sssisis", $nombre, $correo, $hash, $rol, $fecha, $nivel);
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
        //========== IMAGEN DE PERFIL ==========
        public function guardarImagenPerfil($imagen, $idUsuario){
            $extension = validarDocumento($imagen);
            //Si no regreso una extencion
            if ($extension === false) {
                return false;
            }

            $subdirectorio = 'imagenes/';

            //Si no es una imagen
            if (!in_array($extension, ["jpg", "png", "webp"])) {
                return false;
            }

            $directorio = __DIR__ . '/../../../documentos/usuarios/' . $subdirectorio;

            // Crear directorio si no existe...

            $nombre = "imagen_perfil_usuario_{$idUsuario}" . uniqid() . ".{$extension}";

            $rutaFisica = $directorio . $nombre;

            if (!move_uploaded_file($imagen['tmp_name'], $rutaFisica)) {
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
        public function actualizarUsuario($id, $nombre, $correo, $contrasena_anterior,$contrasena, $nivel, $foto){
            $validacion = $this->validarPUT($id, $contrasena_anterior, $correo, $nivel);
            
            //si pasa todas las validaciones
            if($validacion !== 0){
                return $validacion;
            }

            //hasheo de contraseña nueva
            $hash = password_hash($contrasena, PASSWORD_DEFAULT);

            $sql = "UPDATE usuarios SET nombre = ?, correo = ?, contrasena = ?, id_nivel = ?, url_foto = ? WHERE id_usuario = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sssisi", $nombre, $correo, $hash, $nivel, $foto, $id); 
            $stmt->execute();

            return 0;
        }

        //validaciones para PUT
        public function validarPUT($id, $contrasena, $correo, $nivel){
            //validar si la id existe en la base de datos la ID
            $sql = "SELECT * FROM usuarios WHERE id_usuario = ? AND bloqueo_usuario = 0";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id); 
            $stmt->execute();
            if ($stmt->get_result()->num_rows == 0) {
                return 1;
            }

            //validar si la contraseña anterior coincide
            $sql = "SELECT contrasena FROM usuarios WHERE id_usuario = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id); 
            $stmt->execute();
            $resultado = $stmt->get_result();
            $usuario = $resultado->fetch_assoc();
            if(!password_verify($contrasena, $usuario['contrasena'])){
                return 2;
            }

            //validar si el correo ya esta siendo usado por otro usuario
            $sql = "SELECT id_usuario FROM usuarios WHERE correo = ? AND id_usuario != ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $correo, $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                return 3;
            }
            
            //validar si el nivel de estudio se encuentra en la base de datos
            $sql = "SELECT id_nivel FROM nivel_estudio WHERE id_nivel = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $nivel); 
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                return 4;
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