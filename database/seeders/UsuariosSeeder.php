<?php

function seedUsuarios(mysqli $conn) : void 
{
    // Todos los usuarios de prueba comparten esta contraseña
    $contrasenaPrueba = 'Prueba123*';
    $hash = password_hash($contrasenaPrueba, PASSWORD_DEFAULT);

    $usuarios = [
        ["nombre" => 'Ana Martinez',     "correo" => 'ana.martinez@prueba.com',     "rol" => 'ESTUDIANTE',    "nivel" => 'Primaria'],
        ["nombre" => 'Carlos Hernandez', "correo" => 'carlos.hernandez@prueba.com', "rol" => 'ESTUDIANTE',    "nivel" => 'Secundaria'],
        ["nombre" => 'Lucia Ramirez',    "correo" => 'lucia.ramirez@prueba.com',    "rol" => 'ESTUDIANTE',    "nivel" => 'Bachillerato'],
        ["nombre" => 'Roberto Flores',   "correo" => 'roberto.flores@prueba.com',   "rol" => 'PROFESOR',      "nivel" => 'Maestria'],
        ["nombre" => 'Leonel Castillo',  "correo" => 'leonel.castillo@prueba.com',  "rol" => 'ADMINISTRADOR', "nivel" => 'Universidad'],
    ];

    $stmtRol    = $conn->prepare("SELECT id_rol FROM roles WHERE nombre = ?");
    $stmtNivel  = $conn->prepare("SELECT id_nivel FROM nivel_estudio WHERE nombre = ?");
    $stmtBuscar = $conn->prepare("SELECT id_usuario FROM usuarios WHERE correo = ? LIMIT 1");

    $stmtActualizar = $conn->prepare(
        "UPDATE usuarios
         SET id_rol = ?, id_nivel = ?, nombre = ?, contrasena = ?
         WHERE id_usuario = ?"
    );

    // url_foto es NOT NULL sin default, por eso se inserta vacía
    $stmtInsertar = $conn->prepare(
        "INSERT INTO usuarios (nombre, correo, contrasena, id_rol, id_nivel, url_foto)
         VALUES (?, ?, ?, ?, ?, '')"
    );

    foreach ($usuarios as $usuario) {
        $nombreRol   = $usuario["rol"];
        $nombreNivel = $usuario["nivel"];
        $nombre      = $usuario["nombre"];
        $correo      = $usuario["correo"];

        $stmtRol->bind_param("s", $nombreRol);
        $stmtRol->execute();
        $idRol = $stmtRol->get_result()->fetch_assoc()["id_rol"] ?? null;
        if (!$idRol) {
            throw new Exception("No se encontró el rol: $nombreRol");
        }

        $stmtNivel->bind_param("s", $nombreNivel);
        $stmtNivel->execute();
        $idNivel = $stmtNivel->get_result()->fetch_assoc()["id_nivel"] ?? null;
        if (!$idNivel) {
            throw new Exception("No se encontró el nivel de estudio: $nombreNivel");
        }

        $stmtBuscar->bind_param("s", $correo);
        $stmtBuscar->execute();
        $idUsuario = $stmtBuscar->get_result()->fetch_assoc()["id_usuario"] ?? null;

        if ($idUsuario) {
            $stmtActualizar->bind_param("iissi", $idRol, $idNivel, $nombre, $hash, $idUsuario);
            $stmtActualizar->execute();
        } else {
            $stmtInsertar->bind_param("sssii", $nombre, $correo, $hash, $idRol, $idNivel);
            $stmtInsertar->execute();
        }
    }

    $stmtRol->close();
    $stmtNivel->close();
    $stmtBuscar->close();
    $stmtActualizar->close();
    $stmtInsertar->close();

    echo "Usuarios insertados correctamente.\n";
}