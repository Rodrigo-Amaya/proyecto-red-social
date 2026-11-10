<?php
// Leer usuarios
$usuarios = json_decode(
    file_get_contents(__DIR__ . '/database/json/usuarios.json'),
    true
);

// Leer materias
$materias = json_decode(
    file_get_contents(__DIR__ . '/database/json/materias.json'),
    true
);

// Leer publicaciones
$publicaciones = json_decode(
    file_get_contents(__DIR__ . '/database/json/publicaciones.json'),
    true
);

// Usuario de prueba
$usuario = $usuarios[0];

// Relacionar cada publicación con su usuario y materia
foreach ($publicaciones as &$publicacion) {

    foreach ($usuarios as $u) {
        if ($u['id_usuario'] === $publicacion['id_usuario']) {
            $publicacion['autor'] = $u['nombre'];
            $publicacion['url_foto'] = $u['url_foto'];
            break;
        }
    }

    foreach ($materias as $m) {
        if ($m['id_materia'] === $publicacion['id_materia']) {
            $publicacion['materia'] = $m['nombre'];
            break;
        }
    }
}

unset($publicacion);

// Navegacion entre paginas
$page = $_GET['page'] ?? 'inicio';

$pages = [
    'inicio'  => [
        'title' => 'Inicio',
        'view'  => '/public/views/publicaciones/inicio.php'
    ],
    'perfil'   => [
        'title' => 'Perfil',
        'view'  => '/public/views/usuarios/perfil.php'
    ],
    'login' => [
        'title' => 'Login',
        'view'  => '/public/views/auth/login.php'
    ],
];

if (!isset($pages[$page])) {
    $page = 'inicio';
}

$pageTitle = $pages[$page]['title'];
$currentPage = $page;
$contentView = __DIR__ . $pages[$page]['view'];

require_once __DIR__ . '/public/views/shared/layout.php';

?>