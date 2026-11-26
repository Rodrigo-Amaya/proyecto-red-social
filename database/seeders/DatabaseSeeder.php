<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script solo se ejecuta desde la terminal.');
}

require_once __DIR__ . "/../../app/config/database.php";

// Catálogos iniciales
require_once __DIR__ . "/MateriasSeeder.php";
require_once __DIR__ . "/NivelesEstudioSeeder.php";
require_once __DIR__ . "/PalabrasClaveSeeder.php";
require_once __DIR__ . "/RolesSeeder.php";

// Información del sistema
require_once __DIR__ . "/UsuariosSeeder.php";

// Registros de prueba
require_once __DIR__ . "/PublicacionesSeeder.php";
require_once __DIR__ . "/ComentariosSeeder.php";

try {

    $conn->begin_transaction();

    // Catálogos
    seedMaterias($conn);
    seedNivelesEstudio($conn);
    seedPalabrasClave($conn);
    seedRoles($conn);
    
    // Información del sistema
    seedUsuarios($conn);

    // Registros de prueba
    seedPublicaciones($conn);
    seedComentarios($conn);

    $conn->commit();

    echo "Todos los seeders se ejecutaron correctamente.\n";

} catch (Throwable $e) {

    $conn->rollback();

    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}