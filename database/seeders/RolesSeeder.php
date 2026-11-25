<?php

function seedRoles(mysqli $conn): void
{
    $roles = [
        "ESTUDIANTE",
        "PROFESOR",
        "ADMINISTRADOR"
    ];

    $sql = "INSERT INTO roles (nombre)
            VALUES (?)
            ON DUPLICATE KEY UPDATE nombre = VALUES(nombre)";

    $stmt = $conn->prepare($sql);

    foreach ($roles as $rol) {
        $stmt->bind_param("s", $rol);
        $stmt->execute();
    }

    echo "Roles insertados correctamente.\n";
}