<?php
require_once __DIR__ . '/app/helpers/utilidades.php';

$baseUrl = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');

// Usuario de la sesión
$usuario = null;
if (isset($_COOKIE[session_name()])) {
    abrirSesion();
    $idSesion = $_SESSION['id_usuario'] ?? null;

    if (is_int($idSesion)) {
        require_once __DIR__ . '/app/config/database.php';

        // Actualizar sesion
        $stmt = $conn->prepare(
            "SELECT u.id_usuario, u.nombre, u.url_foto, r.nombre AS rol
             FROM usuarios u
             INNER JOIN roles r ON r.id_rol = u.id_rol
             WHERE u.id_usuario = ? AND u.bloqueo_usuario = 0"
        );
        $stmt->bind_param("i", $idSesion);
        $stmt->execute();
        $usuario = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();

        if ($usuario === null) {
            $_SESSION = [];
            session_destroy();
        } else {
            $_SESSION['rol'] = $usuario['rol']; // sesión se actualiza
        }
    }
}

// Navegacion entre paginas
$pages = [
    'inicio'  => [
        'title' => 'Inicio',
        'view'  => '/public/views/publicaciones/inicio.php',
        'protegida' => false
    ],
    'perfil'   => [
        'title' => 'Perfil',
        'view'  => '/public/views/usuarios/perfilUsuarios.php',
        'protegida' => true
    ],
    'login' => [
        'title' => 'Login',
        'view'  => '/public/views/auth/authPage.php',
        'protegida' => false
    ],
    'olvidarContrasena' => [
        'title' => 'Olvidar contrasena',
        'view'  => '/public/views/auth/ingresoCorreo.php',
        'protegida' => false
    ],
    'mis-tareas' => [
        'title' => 'Mis Tareas',
        'view'  => '/public/views/publicaciones/misTareas.php',
        'protegida' => true
    ],
    'guardados' => [
        'title' => 'Guardados',
        'view'  => '/public/views/publicaciones/guardados.php',
        'protegida' => true
    ],
    'publicacion' => [
        'title' => 'Publicación',
        'view'  => '/public/views/publicaciones/detalle.php',
        'protegida' => false
    ],
];

$page = $_GET['page'] ?? 'inicio';
if (!is_string($page) || !isset($pages[$page])) {
    $page = 'inicio';
}

// Redireccion a paginas tras iniciar sesion
$volver = $_GET['volver'] ?? 'inicio';
if (!is_string($volver) || !isset($pages[$volver]) || $volver === 'login') {
    $volver = 'inicio';
}

// Pagina protegidas necesitan iniciar sesion
if ($pages[$page]['protegida'] && $usuario === null) {
    header('Location: index.php?page=login&volver=' . urlencode($page));
    exit;
}

// Ya hay sesion cerrar login
if ($page === 'login' && $usuario !== null) {
    header('Location: index.php?page=' . urlencode($volver));
    exit;
}

//Validadr id de publicacion
$idPublicacion = null;
if ($page === 'publicacion') {
    $idPublicacion = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
    if ($idPublicacion === false || $idPublicacion <= 0) {
        header('Location: index.php?page=inicio');
        exit;
    }
}

$pageTitle = $pages[$page]['title'];
$currentPage = $page;
$contentView = __DIR__ . $pages[$page]['view'];

if (!is_file($contentView)) {
    $page = 'inicio';
    $pageTitle = $pages['inicio']['title'];
    $currentPage = 'inicio';
    $contentView = __DIR__ . $pages['inicio']['view'];
}

if ($page === 'login' || $page === 'olvidarContrasena') {
    require $contentView;
    exit;
}

require_once __DIR__ . '/public/views/shared/layout.php';