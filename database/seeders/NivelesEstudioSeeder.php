<?php

function seedNivelesEstudio(mysqli $conn): void
{
    $niveles = [
        "Primaria",
        "Secundaria",
        "Bachillerato",
        "Universidad",
        "Maestria" 
    ];

    $sql = "INSERT INTO nivel_estudio (nombre)
            VALUES (?)
            ON DUPLICATE KEY UPDATE nombre = VALUES(nombre)";

    $stmt = $conn->prepare($sql);

    foreach ($niveles as $nivel) {
        $stmt->bind_param("s", $nivel);
        $stmt->execute();
    }

    echo "Niveles de estudio insertados correctamente.\n";
}