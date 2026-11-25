<?php
// Devuelve la respuesta JSON
function respuesta(int $codigo, array $datos): void
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

// Lee el body y responde 400 si es invalido
function leerJson(): array
{
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        respuesta(400, ['success' => false, 'mensaje' => 'JSON inválido']);
    }
    return $data;
}

// Leer y validar IDs
function validarId(mixed $valor, string $mensaje = "ID invalido") : int
{
    $id = filter_var($valor, FILTER_VALIDATE_INT);
    if ($id === false || $id <= 0) {
            respuesta(400, ['success' => false, 'mensaje' => $mensaje]);
    }
    return $id;
}

function noPermitido(): void
{
    respuesta(405, ['success' => false, 'mensaje' => 'Método no permitido']);
}

function noEncontrado(): void
{
    respuesta(404, ['success' => false, 'mensaje' => 'Ruta no encontrada']);
}

//=====================================================================

function validarCampoEntero($campo): int
{
    $valor = filter_var($campo, FILTER_VALIDATE_INT);

    if ($valor === false || $valor <= 0) {
        respuesta(400, [
            'success' => false,
            'mensaje' => 'Campo inválido'
        ]);
    }

    return $valor;
}

function validarCamposVacios($campos = []){
    $vacio = true;
    foreach($campos as $campo){
        if($campo !== '' && $campo !== NULL){
            $vacio = false; break;
        }
    }
    if($vacio){
        respuesta(400, ['success' => false, 'mensaje' => 'Campo vacio']);
    }
}

function validarFecha($fecha){
    $fechaValidada = DateTime::createFromFormat('!Y-m-d', $fecha);
    if (!$fechaValidada || $fechaValidada->format('Y-m-d') !== $fecha || $fechaValidada > new DateTime('today')){
        respuesta(400, ['success' => false, 'mensaje' => 'Tipo de fecha ingresada no es valida']);
    }
}

//=====================================================================
function validarContrasena(string $contrasena) {
    $patronMayus = '/^(?=.*[A-Z]).+$/';
    $patronEspecial = '/^(?=.*[^a-zA-Z0-9]).+$/';
    $patronNum = '/^(?=.*[0-9]).+$/';

    if(mb_strlen($contrasena) < 8){
        respuesta(400, ['success' => false, 'mensaje' => 'La contraseña debe tener al menos 8 caracteres']);
    }

    if(preg_match($patronMayus, $contrasena) === 0){
        respuesta(400, ['success' => false, 'mensaje' => 'La contraseña debe tener al menos una letra en mayuscula']);
    }
    if(preg_match($patronEspecial, $contrasena) === 0){
        respuesta(400, ['success' => false, 'mensaje' => 'La contraseña debe tener al menos un caracter especial']);
    }
    if(preg_match($patronNum, $contrasena) === 0){
        respuesta(400, ['success' => false, 'mensaje' => 'La contraseña debe tener al menos un numero']);
    }
}


function validarTrueOFalse(int $valor){
    if($valor !== 0 && $valor !== 1){
        respuesta(400, ['success' => false, 'mensaje' => 'Valor invalido de campo de verdadero o falso']);
    }
}

function validarDocumento($archivo) {
    //Si hubo algun error a la hora de subir el archivo
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    if (!is_array($archivo)) {
        return false;
    }

    //Si el archivo es muy grande
    if ($archivo['size'] > 10 * 1024 * 1024) {
        return false;
    }

    //Tipos de archivos permitidos por el sistema
    $tiposPermitidos = [
        // Documentos
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.ms-powerpoint' => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',

        // Imágenes
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo === false) {
        return false;
    }
    if (!is_file($archivo['tmp_name'])) {
        return false;
    }
    $tipo = finfo_file($finfo, $archivo['tmp_name']);

    return $tiposPermitidos[$tipo] ?? false;
}

//========== SESION ==========

function abrirSesion(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/**
 * Obtener el id del usuario autenticado
 * @return int ID usuario, respuesta 401 si no hay sesión
 */
function usuarioActual(): int
{
    if (!isset($_SESSION['id_usuario'])) {
        respuesta(401, ['success' => false, 'mensaje' => 'No autenticado']);
    }
    return (int)$_SESSION['id_usuario'];
}

/**
 * Validar que el usuario tenga el rol requerido
 * @param array $roles Nombres de roles permitidos
 * @return void Respuestas: 401 sin sesión|403 si el rol no está permitido
 */
function requerirRol(array $roles): void
{
    usuarioActual();
    if (!in_array($_SESSION['rol'] ?? '', $roles, true)) {
        respuesta(403, ['success' => false, 'mensaje' => 'No tiene permiso para esta acción']);
    }
}

/**
 * Validar todos los campos obligatorios
 * @return void Devuelve respuesta HTTP si hay campos invalidos
 */
function validarCamposRequeridos(array $data, array $requeridos) : void
{
    $invalidos = [];

    foreach ($requeridos as $campo) {
        if (
            !isset($data[$campo]) ||
            !is_scalar($data[$campo]) ||
            trim((string)$data[$campo]) === ''
        ) {
            $invalidos[] = $campo;
        }
    }

    if (!empty($invalidos)) {
        respuesta(400, [
            'success' => false,
            'mensaje' => 'Campos inválidos o vacíos',
            'campos' => $invalidos
        ]);
    }
}
