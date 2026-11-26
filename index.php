<?php
require_once __DIR__ . '/app/helpers/utilidades.php';

// IDs de la tabla `roles` (los mismos de Profesor::ROL_PROFESOR y ROL_ADMIN).
// Se definen aquí para que todas las vistas incluidas los compartan.
const ROL_PROFESOR = 2;
const ROL_ADMIN = 3;

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
            "SELECT u.id_usuario, u.nombre, u.url_foto, u.id_rol, r.nombre AS rol
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
            $usuario['id_rol'] = (int)$usuario['id_rol'];
            $_SESSION['rol'] = $usuario['rol']; // sesión se actualiza
            $_SESSION['id_rol'] = $usuario['id_rol'];
        }
    }
}

// Navegacion entre paginas
// 'admin' => true: solo administradores (se valida con el rol leído de la base)
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
    'solicitudes' => [
        'title' => 'Solicitudes de profesores',
        'view'  => '/public/views/administrador/revisarSolicitud.php',
        'protegida' => true,
        'admin' => true
    ],
    'gestion-usuarios' => [
        'title' => 'Gestión de usuarios',
        'view'  => '/public/views/administrador/gestionUsuarios.php',
        'protegida' => true,
        'admin' => true
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

// Paginas de administrador: solo rol administrador
if (!empty($pages[$page]['admin']) && ($usuario['id_rol'] ?? 0) !== ROL_ADMIN) {
    header('Location: index.php?page=inicio');
    exit;
}
if (isset($_GET['debug'])) {
    var_dump($page, $usuario['id_rol'] ?? null, ROL_ADMIN, is_file(__DIR__ . $pages[$page]['view']));
    exit;
}
// Ya hay sesion cerrar login
if ($page === 'login' && $usuario !== null) {
    header('Location: index.php?page=' . urlencode($volver));
    exit;
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