<?php

function seedPublicaciones(mysqli $conn): void
{
    $publicaciones = [
        ["correo" => 'ana.martinez@prueba.com', "materia" => 'Matematicas', "fecha" => '2026-11-01 09:00:00', "puntos" => 30, "estado" => 'SINRESOLVER',
         "titulo" => 'Ayuda con ecuaciones cuadraticas',
         "mensaje" => 'Alguien puede explicarme como resolver ecuaciones cuadraticas por factorizacion? Estoy estudiando para un examen.'],
        ["correo" => 'carlos.hernandez@prueba.com', "materia" => 'Biologia', "fecha" => '2026-11-01 14:30:00', "puntos" => 28, "estado" => 'RESUELTA',
         "titulo" => 'Dudas sobre fotosintesis',
         "mensaje" => 'Podrian explicarme como las plantas utilizan la luz solar para producir su alimento?'],
        ["correo" => 'lucia.ramirez@prueba.com', "materia" => 'Programacion', "fecha" => '2026-11-02 10:15:00', "puntos" => 45, "estado" => 'SINRESOLVER',
         "titulo" => 'Diferencia entre while y for',
         "mensaje" => 'Cuando conviene usar un ciclo while en lugar de un for? Me confundo al escribir los ejercicios de la clase.'],
        ["correo" => 'roberto.flores@prueba.com', "materia" => 'Fisica', "fecha" => '2026-11-02 16:00:00', "puntos" => 40, "estado" => 'SINRESOLVER',
         "titulo" => 'Ejercicio de caida libre',
         "mensaje" => 'Comparto un ejercicio de caida libre para practicar. Pueden calcular el tiempo que tarda un objeto en caer desde 20 metros?'],
        ["correo" => 'leonel.castillo@prueba.com', "materia" => 'Otros', "fecha" => '2026-11-03 08:45:00', "puntos" => 20, "estado" => 'RESUELTA',
         "titulo" => 'Como organizar mi tiempo de estudio',
         "mensaje" => 'Que tecnicas usan para organizar sus horarios de estudio durante la semana de examenes?'],
        ["correo" => 'ana.martinez@prueba.com', "materia" => 'Ingles', "fecha" => '2026-11-03 13:20:00', "puntos" => 32, "estado" => 'SINRESOLVER',
         "titulo" => 'Uso del present perfect',
         "mensaje" => 'No entiendo cuando usar present perfect y cuando past simple. Alguien tiene ejemplos claros?'],
        ["correo" => 'carlos.hernandez@prueba.com', "materia" => 'Bases de Datos', "fecha" => '2026-11-04 11:00:00', "puntos" => 50, "estado" => 'SINRESOLVER',
         "titulo" => 'Como funciona un JOIN en SQL',
         "mensaje" => 'Me cuesta entender la diferencia entre INNER JOIN y LEFT JOIN. Podrian explicarlo con un ejemplo sencillo?'],
        ["correo" => 'lucia.ramirez@prueba.com', "materia" => 'Historia', "fecha" => '2026-11-04 15:40:00', "puntos" => 25, "estado" => 'RESUELTA',
         "titulo" => 'Causas de la independencia de Centroamerica',
         "mensaje" => 'Necesito un resumen de las principales causas de la independencia de Centroamerica para una exposicion.'],
        ["correo" => 'roberto.flores@prueba.com', "materia" => 'Quimica', "fecha" => '2026-11-05 09:30:00', "puntos" => 38, "estado" => 'SINRESOLVER',
         "titulo" => 'Balanceo de ecuaciones quimicas',
         "mensaje" => 'Dejo un ejercicio: balancear la ecuacion de combustion del metano. Expliquen el procedimiento paso a paso.'],
        ["correo" => 'leonel.castillo@prueba.com', "materia" => 'Estadistica', "fecha" => '2026-11-05 17:10:00', "puntos" => 35, "estado" => 'SINRESOLVER',
         "titulo" => 'Media, mediana y moda',
         "mensaje" => 'Cuando conviene usar la mediana en lugar de la media? Busco ejemplos con datos reales.'],
    ];

    $stmtUsuario = $conn->prepare("SELECT id_usuario FROM usuarios WHERE correo = ?");
    $stmtExiste  = $conn->prepare("SELECT 1 FROM publicaciones WHERE id_usuario = ? AND titulo = ?");

    // La materia se busca por nombre, para no depender de IDs fijos
    $sql = "INSERT INTO publicaciones (titulo, fecha, mensaje, puntos, id_usuario, id_materia, estado)
            SELECT ?, ?, ?, ?, ?, id_materia, ? FROM materias WHERE nombre = ?";
    $stmt = $conn->prepare($sql);

    foreach ($publicaciones as $p) {
        $correo = $p["correo"];
        $titulo = $p["titulo"];
        $fecha = $p["fecha"];
        $mensaje = $p["mensaje"];
        $puntos = $p["puntos"];
        $estado = $p["estado"];
        $nombreMateria = $p["materia"];

        $stmtUsuario->bind_param("s", $correo);
        $stmtUsuario->execute();
        $fila = $stmtUsuario->get_result()->fetch_assoc();
        if (!$fila) {
            throw new Exception("No existe el usuario $correo. Ejecuta primero seedUsuarios.");
        }
        $idUsuario = (int)$fila['id_usuario'];

        // Repetible: si ya existe esa publicación para ese usuario, se omite
        $stmtExiste->bind_param("is", $idUsuario, $titulo);
        $stmtExiste->execute();
        if ($stmtExiste->get_result()->num_rows > 0) {
            continue;
        }

        $stmt->bind_param("sssiiss", $titulo, $fecha, $mensaje, $puntos, $idUsuario, $estado, $nombreMateria);
        $stmt->execute();
        if ($stmt->affected_rows === 0) {
            throw new Exception("No existe la materia $nombreMateria.");
        }
    }

    $stmtUsuario->close();
    $stmtExiste->close();
    $stmt->close();

    echo "Publicaciones insertadas correctamente.\n";
}