<?php

    class ProfesoresController implements Controlador
        {
            private const GRADOS = ['Técnico', 'Licenciatura', 'Ingeniería', 'Maestría', 'Doctorado'];
            private const ESTADOS = ['PENDIENTE', 'EN_SEGUNDA_REVISION', 'APROBADA', 'RECHAZADA', 'CANCELADA'];

            public function __construct(
                private Profesor $modelo
            ) {}

            //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //========== CONTROL DE METODOS ==========
            public function control(string $metodo, array $partes): void
            {
                $id = $partes[1] ?? null;

                //PHP no llena $_POST con un PUT real, por eso se envia como POST?_method=PUT
                if ($metodo === 'POST' && $id !== null && strtoupper($_GET['_method'] ?? '') === 'PUT') {
                    $metodo = 'PUT';
                }

                // url: /profesores
                if ($id === null) {
                    match ($metodo) {
                        'GET'  => $this->buscarAplicacion(),
                        'POST' => $this->ingresarNuevaAplicacion(),
                        default => noPermitido(),
                    };
                    return;
                }

                // url: profesores/{id}
                $idAplicacion = validarId($id, 'ID inválido');

                if (isset($partes[2])) {
                    noEncontrado();
                }

                match ($metodo) {
                    'GET'    => $this->obtenerAplicacion($idAplicacion),
                    'PUT'    => $this->actualizarAplicacion($idAplicacion),
                    'DELETE' => $this->cancelarAplicacion($idAplicacion),
                    'PATCH'  => $this->revisarAplicacion($idAplicacion),
                    default  => noPermitido(),
                };
            }

            //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //========== GET ==========

            //busqueda de aplicacion por id
            function obtenerAplicacion(int $id): void{
                $resultado = $this->modelo->obtenerAplicacion($id);

                //Sino hay resultado
                if (!$resultado) {
                    respuesta(404, ['success' => false, 'Mensaje' => 'Aplicación no encontrada']);
                }

                respuesta(200, [
                    'success' => true,
                    'Aplicacion' => $resultado
                ]);
            }

            //Busqueda de aplicacion/es
            function buscarAplicacion(): void{
                if(isset($_GET["busqueda"]) ||
                isset($_GET["estado"]) ||
                isset($_GET["usuario"]) ||
                isset($_GET["orden"])){
                    $busqueda = trim($_GET["busqueda"] ?? '');
                    $estado = strtoupper(trim($_GET["estado"] ?? ''));
                    $usuario = trim($_GET["usuario"] ?? '');
                    $orden = strtoupper(trim($_GET["orden"] ?? ''));

                    //Validamos que el estado sea uno correcto
                    if($estado !== '' && !in_array($estado, self::ESTADOS, true)){
                        respuesta(400, ['success' => false, 'Mensaje' => 'Tipo de estado inválido']);
                    }

                    //Validamos si la id de usuario es un numero entero
                    if($usuario !== ''){
                        validarCampoEntero($usuario);
                    }

                    //Validamos el orden
                    if($orden !== '' && $orden !== 'ASC' && $orden !== 'DESC'){
                        respuesta(400, ['success' => false, 'Mensaje' => 'Tipo de orden inválido']);
                    }

                    $resultado = $this->modelo->buscarPorFiltros(
                        $busqueda,
                        $estado,
                        $usuario,
                        $orden
                    );
                }

                //Listar todas las aplicaciones
                else{
                    $resultado = $this->modelo->listarAplicaciones();
                }

                //Mensaje recibido
                if($resultado){
                    respuesta(200, ['success' => true, 'Aplicaciones' => $resultado]);
                }
                else{
                    respuesta(200, ['success' => true, 'Aplicaciones' => '']);
                }
            }

            //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //========== POST ==========
            function ingresarNuevaAplicacion(): void {
                //Si hay sesion iniciada se usa ese usuario, sino el que llegue por POST
                $usuario = $this->idSesion() ?? trim($_POST["id_usuario"] ?? '');
                $universidad = trim($_POST["universidad"] ?? '');
                $telefono = trim($_POST["telefono_universidad"] ?? '');
                $carrera = trim($_POST["carrera"] ?? '');
                $grado = trim($_POST["grado_academico"] ?? '');
                $anio = trim($_POST["anio_graduacion"] ?? '');
                $archivo = $_FILES["titulo"] ?? NULL;

                //Si alguno de los campos obligatorios esta vacio
                if ($usuario === '' || $universidad === '' || $carrera === '' || $grado === '' || $anio === '') {
                    respuesta(400, ['success' => false, 'Mensaje' => 'Todos los campos son obligatorios']);
                }

                if ($archivo === NULL || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
                    respuesta(400, ['success' => false, 'Mensaje' => 'Debe adjuntar su título']);
                }

                $this->validarCamposAplicacion($usuario, $universidad, $telefono, $carrera, $grado, $anio);

                $extension = strtolower((string)validarDocumento($archivo));
                if (!in_array($extension, ["pdf", "jpg", "jpeg", "png", "webp"], true)) {
                    respuesta(400, ['success' => false, 'Mensaje' => 'El título debe ser un PDF o una imagen válida']);
                }

                $resultado = $this->modelo->aplicar(
                    (int)$usuario,
                    $universidad,
                    $telefono !== '' ? $telefono : null,
                    $carrera,
                    $grado,
                    (int)$anio,
                    $archivo
                );

                //Respuesta obtenida
                switch ($resultado) {
                    case 1:
                        respuesta(409, ['success' => false, 'Mensaje' => 'Usuario no encontrado o bloqueado']);
                        break;
                    case 2:
                        respuesta(409, ['success' => false, 'Mensaje' => 'El usuario ya es profesor']);
                        break;
                    case 3:
                        respuesta(409, ['success' => false, 'Mensaje' => 'Ya tiene una aplicación en revisión']);
                        break;
                    case 4:
                        respuesta(500, ['success' => false, 'Mensaje' => 'Error al guardar la aplicación o el título']);
                        break;
                    default:
                        respuesta(201, ['success' => true, 'Mensaje' => 'Aplicación enviada con éxito, será revisada pronto']);
                }
            }

            //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //========== PUT ==========
            //Se envia como POST /profesores/{id}?_method=PUT (multipart) para poder reemplazar el titulo
            function actualizarAplicacion(int $id): void {
                $universidad = trim($_POST["universidad"] ?? '');
                $telefono = trim($_POST["telefono_universidad"] ?? '');
                $carrera = trim($_POST["carrera"] ?? '');
                $grado = trim($_POST["grado_academico"] ?? '');
                $anio = trim($_POST["anio_graduacion"] ?? '');
                $archivo = $_FILES["titulo"] ?? NULL;

                if ($universidad === '' || $carrera === '' || $grado === '' || $anio === '') {
                    respuesta(400, ['success' => false, 'Mensaje' => 'Universidad, carrera, grado y año son obligatorios']);
                }

                //El titulo es opcional al editar: si no se sube, se conserva el actual
                if ($archivo !== NULL && $archivo['error'] === UPLOAD_ERR_NO_FILE) {
                    $archivo = NULL;
                }

                $this->validarCamposAplicacion('0', $universidad, $telefono, $carrera, $grado, $anio);

                if ($archivo !== NULL) {
                    $extension = strtolower((string)validarDocumento($archivo));
                    if (!in_array($extension, ["pdf", "jpg", "jpeg", "png", "webp"], true)) {
                        respuesta(400, ['success' => false, 'Mensaje' => 'El título debe ser un PDF o una imagen válida']);
                    }
                }

                $resultado = $this->modelo->actualizarAplicacion(
                    $id,
                    $universidad,
                    $telefono !== '' ? $telefono : null,
                    $carrera,
                    $grado,
                    (int)$anio,
                    $archivo
                );

                switch ($resultado) {
                    case 1:
                        respuesta(404, ['success' => false, 'Mensaje' => 'Aplicación no encontrada']);
                        break;
                    case 2:
                        respuesta(403, ['success' => false, 'Mensaje' => 'Ya no se puede modificar la aplicación']);
                        break;
                    case 3:
                        respuesta(500, ['success' => false, 'Mensaje' => 'Error al guardar los cambios o el título']);
                        break;
                    default:
                        respuesta(200, ['success' => true, 'Mensaje' => 'Aplicación actualizada correctamente']);
                }
            }

            //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //========== DELETE ==========
            //Cancela la aplicacion (estado CANCELADA), no borra el registro
            function cancelarAplicacion(int $id): void {
                $resultado = $this->modelo->cancelarAplicacion(
                    $id
                );

                //Resultado recibido
                if($resultado === false){
                    respuesta(404, ['success' => false, 'Mensaje' => 'La aplicación no fue encontrada o ya no puede cancelarse']);
                }
                else{
                    respuesta(200, ['success' => true, 'Mensaje' => 'Aplicación cancelada correctamente']);
                }
            }

            //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //========== PATCH ==========
            //Revision del administrador. Body JSON: {"decision": "APROBADA" | "RECHAZADA", "comentario": "..."}
            function revisarAplicacion(int $id): void {
                $idAdministrador = $this->idSesion();

                if ($idAdministrador === null) {
                    respuesta(401, ['success' => false, 'Mensaje' => 'Debe iniciar sesión']);
                }

                $datos = leerJson();

                $decision = strtoupper(trim($datos['decision'] ?? ''));
                $comentario = trim($datos['comentario'] ?? '');

                if ($decision !== 'APROBADA' && $decision !== 'RECHAZADA') {
                    respuesta(400, ['success' => false, 'Mensaje' => 'Decisión inválida']);
                }

                //Al rechazar es obligatorio explicar la razon
                if ($decision === 'RECHAZADA' && $comentario === '') {
                    respuesta(400, ['success' => false, 'Mensaje' => 'Debe indicar la razón del rechazo']);
                }

                $resultado = $this->modelo->revisarAplicacion(
                    $id,
                    $idAdministrador,
                    $decision,
                    $comentario !== '' ? $comentario : null
                );

                //Resultado recibido
                switch ($resultado) {
                    case 1:
                        respuesta(403, ['success' => false, 'Mensaje' => 'No tiene permisos de administrador']);
                        break;
                    case 2:
                        respuesta(404, ['success' => false, 'Mensaje' => 'Aplicación no encontrada']);
                        break;
                    case 3:
                        respuesta(409, ['success' => false, 'Mensaje' => 'La aplicación ya no está en revisión']);
                        break;
                    case 4:
                        respuesta(500, ['success' => false, 'Mensaje' => 'Error al guardar la revisión']);
                        break;
                    default:
                        respuesta(200, ['success' => true, 'Mensaje' => 'Revisión registrada correctamente']);
                }
            }

            //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //========== AUXILIARES ==========

            //Id del usuario en sesion (null si no hay sesion)
            private function idSesion(): ?int {
                if (session_status() !== PHP_SESSION_ACTIVE) {
                    session_start();
                }
                return isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : null;
            }

            //Validaciones de formato compartidas por POST y PUT
            private function validarCamposAplicacion(string $usuario, string $universidad, string $telefono, string $carrera, string $grado, string $anio): void {
                validarCampoEntero($usuario);

                if (mb_strlen($universidad) > 200) {
                    respuesta(400, ['success' => false, 'Mensaje' => 'Nombre de universidad muy largo']);
                }

                if (mb_strlen($carrera) > 150) {
                    respuesta(400, ['success' => false, 'Mensaje' => 'Nombre de carrera muy largo']);
                }

                if ($telefono !== '' && !preg_match('/^[0-9+\-\s()]{7,30}$/', $telefono)) {
                    respuesta(400, ['success' => false, 'Mensaje' => 'Formato de teléfono incorrecto']);
                }

                if (!in_array($grado, self::GRADOS, true)) {
                    respuesta(400, ['success' => false, 'Mensaje' => 'Grado académico inválido']);
                }

                if (!ctype_digit($anio) || (int)$anio < 1950 || (int)$anio > (int)date('Y')) {
                    respuesta(400, ['success' => false, 'Mensaje' => 'Año de graduación inválido']);
                }
            }
        }
?>