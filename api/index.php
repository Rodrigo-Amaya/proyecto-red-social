<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../app/helpers/utilidades.php';
require_once __DIR__ . '/../app/contracts/Controlador.php';

if (isset($_COOKIE[session_name()])) {
    abrirSesion();
}

spl_autoload_register(function (string $clase) {
    // Módulos: src/<Modulo>/Controllers y src/<Modulo>/Models
    foreach (['Controller', 'Model'] as $carpeta) {
        $archivos = glob(__DIR__ . "/../src/*/$carpeta/$clase.php");
        if ($archivos) {
            require_once $archivos[0];
            return;
        }
    }
});

try {
    require_once __DIR__ . '/../app/config/database.php'; // define $conn

    // /proyecto/api/usuarios/5 
    $base   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $ruta   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $partes = array_values(array_filter(explode('/', substr($ruta, strlen($base)))));
    $metodo = $_SERVER['REQUEST_METHOD'];

    // Una línea por recurso; el controlador se crea solo si se pide
    $rutas = [
        'usuarios'  => fn() => new usuarioController(new usuario($conn)),
        'auth'  => fn() => new AuthController(new usuario($conn)),
        'niveles'  => fn() => new NivelEstudioController(new NivelEstudio($conn)),
        'publicaciones'  => fn() => new publicacionController(new publicacion($conn)),
        'profesores'  => fn() => new profesoresController(new profesor($conn)),
        'materias'  => fn() => new MateriaController(new Materia($conn)),
        'notificaciones' => fn() => new NotificacionController(new Notificacion($conn)),
    ];

    $recurso = $partes[0] ?? '';

    if (!isset($rutas[$recurso])) {
        noEncontrado();
        return;
    }

    // publicaciones/{id}/comentario[/{idComentario}]
    if (
        $recurso === 'publicaciones' &&
        ($partes[2] ?? null) === 'comentario'
    ) {
        $controlador = new comentarioController(
            new comentario($conn)
        );
    } else {
        $controlador = $rutas[$recurso]();
    }

    $controlador->control($metodo, $partes);

} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    if ($e->getCode() === 1062) {
        respuesta(409, ['success' => false, 'mensaje' => 'Registro duplicado']);
        return;
    }
    respuesta(500, ['success' => false, 'mensaje' => 'Error interno del servidor']);
} catch (Throwable $e) {
    error_log($e->getMessage());
    respuesta(500, ['success' => false, 'mensaje' => 'Error interno del servidor']);
}