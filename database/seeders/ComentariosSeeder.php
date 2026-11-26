<?php

function seedComentarios(mysqli $conn): void
{
    // ---------- Usuario bloqueado (para probar ese caso del árbol) ----------
    $correoBloqueado = 'bloqueado@prueba.com';

    $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE correo = ? LIMIT 1");
    $stmt->bind_param("s", $correoBloqueado);
    $stmt->execute();
    $existe = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existe) {
        $stmt = $conn->prepare("UPDATE usuarios SET bloqueo_usuario = 1 WHERE id_usuario = ?");
        $idExistente = (int)$existe['id_usuario'];
        $stmt->bind_param("i", $idExistente);
        $stmt->execute();
        $stmt->close();
    } else {
        $nombre = 'Usuario Bloqueado';
        $hash = password_hash('Prueba123*', PASSWORD_DEFAULT);
        $stmt = $conn->prepare(
            "INSERT INTO usuarios (nombre, correo, contrasena, id_rol, id_nivel, url_foto, bloqueo_usuario)
             SELECT ?, ?, ?, r.id_rol, n.id_nivel, '', 1
             FROM roles r, nivel_estudio n
             WHERE r.nombre = 'ESTUDIANTE' AND n.nombre = 'Primaria'"
        );
        $stmt->bind_param("sss", $nombre, $correoBloqueado, $hash);
        $stmt->execute();
        if ($stmt->affected_rows === 0) {
            throw new Exception("No se pudo crear el usuario bloqueado (revisa roles y niveles de estudio).");
        }
        $stmt->close();
    }

    // ---------- Comentarios ----------
    // "clave" identifica el comentario dentro de este arreglo; "padre" referencia la clave de otro.
    $comentarios = [
        // ===== Publicación A  =====
        ["clave" => 'a1', "pub" => 'Ayuda con ecuaciones cuadraticas', "correo" => 'carlos.hernandez@prueba.com', "padre" => null,
         "fecha" => '2026-11-01 09:30:00', "titulo" => null, "bloquear" => 0,
         "mensaje" => 'Busca dos numeros que multipliquen el termino independiente y sumen el coeficiente de x.'],
        ["clave" => 'a1a', "pub" => 'Ayuda con ecuaciones cuadraticas', "correo" => 'ana.martinez@prueba.com', "padre" => 'a1',
         "fecha" => '2026-11-01 09:45:00', "titulo" => null, "bloquear" => 0,
         "mensaje" => 'Gracias! Y si el coeficiente de x al cuadrado no es 1, como cambia el metodo?'],
        ["clave" => 'a1a1', "pub" => 'Ayuda con ecuaciones cuadraticas', "correo" => 'roberto.flores@prueba.com', "padre" => 'a1a',
         "fecha" => '2026-11-01 10:00:00', "titulo" => null, "bloquear" => 0,
         "mensaje" => 'Ahi conviene multiplicar y dividir por ese coeficiente, o usar la formula general directamente.'],
        ["clave" => 'a1a1a', "pub" => 'Ayuda con ecuaciones cuadraticas', "correo" => 'lucia.ramirez@prueba.com', "padre" => 'a1a1',
         "fecha" => '2026-11-01 10:20:00', "titulo" => null, "bloquear" => 0,
         "mensaje" => 'A mi me resulta mas facil la formula general, siempre funciona.'],
        ["clave" => 'a1a1a1', "pub" => 'Ayuda con ecuaciones cuadraticas', "correo" => 'ana.martinez@prueba.com', "padre" => 'a1a1a',
         "fecha" => '2026-11-01 10:35:00', "titulo" => null, "bloquear" => 0,
         "mensaje" => 'Voy a practicar con las dos y comparo resultados.'],
        ["clave" => 'a2', "pub" => 'Ayuda con ecuaciones cuadraticas', "correo" => 'roberto.flores@prueba.com', "padre" => null,
         "fecha" => '2026-11-01 11:00:00', "titulo" => 'Resumen del metodo', "bloquear" => 0,
         "mensaje" => 'Pasos: 1) igualar a cero, 2) factorizar, 3) igualar cada factor a cero y despejar.'],
        ["clave" => 'a3', "pub" => 'Ayuda con ecuaciones cuadraticas', "correo" => 'lucia.ramirez@prueba.com', "padre" => null,
         "fecha" => '2026-11-01 12:00:00', "titulo" => null, "bloquear" => 0,
         "mensaje" => 'Si quieres ejercicios para practicar, puedo compartirte algunos.'],

        // ===== Publicación B: casos especiales =====
        ["clave" => 'b1', "pub" => 'Diferencia entre while y for', "correo" => 'roberto.flores@prueba.com', "padre" => null,
         "fecha" => '2026-11-02 10:30:00', "titulo" => null, "bloquear" => 0,
         "mensaje" => 'Usa for cuando sabes cuantas veces repetir, y while cuando depende de una condicion.'],
        // Eliminado CON respuestas: debe verse como "[comentario eliminado]"
        ["clave" => 'b2', "pub" => 'Diferencia entre while y for', "correo" => 'carlos.hernandez@prueba.com', "padre" => null,
         "fecha" => '2026-11-02 11:00:00', "titulo" => null, "bloquear" => 1,
         "mensaje" => 'Este comentario fue eliminado por su autor.'],
        ["clave" => 'b2a', "pub" => 'Diferencia entre while y for', "correo" => 'lucia.ramirez@prueba.com', "padre" => 'b2',
         "fecha" => '2026-11-02 11:15:00', "titulo" => null, "bloquear" => 0,
         "mensaje" => 'Estoy de acuerdo con lo que decia, aunque ya no se pueda ver.'],
        // Eliminado SIN respuestas: debe ocultarse
        ["clave" => 'b3', "pub" => 'Diferencia entre while y for', "correo" => 'ana.martinez@prueba.com', "padre" => null,
         "fecha" => '2026-11-02 12:00:00', "titulo" => null, "bloquear" => 1,
         "mensaje" => 'Comentario eliminado sin respuestas.'],
        // De usuario bloqueado, CON respuestas: debe verse como "[comentario eliminado]"
        ["clave" => 'b4', "pub" => 'Diferencia entre while y for', "correo" => $correoBloqueado, "padre" => null,
         "fecha" => '2026-11-02 13:00:00', "titulo" => null, "bloquear" => 0,
         "mensaje" => 'Comentario de un usuario que luego fue bloqueado.'],
        ["clave" => 'b4a', "pub" => 'Diferencia entre while y for', "correo" => 'roberto.flores@prueba.com', "padre" => 'b4',
         "fecha" => '2026-11-02 13:20:00', "titulo" => null, "bloquear" => 0,
         "mensaje" => 'Respuesta a un comentario cuyo autor esta bloqueado.'],
    ];

    $stmtUsuario = $conn->prepare("SELECT id_usuario FROM usuarios WHERE correo = ?");
    $stmtPub     = $conn->prepare("SELECT id_publicacion FROM publicaciones WHERE titulo = ? LIMIT 1");
    $stmtExiste  = $conn->prepare("SELECT id_comentario FROM comentarios WHERE id_usuario = ? AND id_publicacion = ? AND mensaje = ? LIMIT 1");
    $stmtInsertar = $conn->prepare(
        "INSERT INTO comentarios (titulo, fecha, mensaje, id_usuario, id_publicacion, id_comentario_padre, bloquear)
         VALUES (?,?,?,?,?,?,?)"
    );

    $ids = []; // clave => id_comentario real

    foreach ($comentarios as $c) {
        $correo = $c["correo"];
        $tituloPub = $c["pub"];

        $stmtUsuario->bind_param("s", $correo);
        $stmtUsuario->execute();
        $fila = $stmtUsuario->get_result()->fetch_assoc();
        if (!$fila) {
            throw new Exception("No existe el usuario $correo. Ejecuta primero seedUsuarios.");
        }
        $idUsuario = (int)$fila['id_usuario'];

        $stmtPub->bind_param("s", $tituloPub);
        $stmtPub->execute();
        $fila = $stmtPub->get_result()->fetch_assoc();
        if (!$fila) {
            throw new Exception("No existe la publicación '$tituloPub'. Ejecuta primero seedPublicaciones.");
        }
        $idPublicacion = (int)$fila['id_publicacion'];

        $mensaje = $c["mensaje"];

        $stmtExiste->bind_param("iis", $idUsuario, $idPublicacion, $mensaje);
        $stmtExiste->execute();
        $existente = $stmtExiste->get_result()->fetch_assoc();
        if ($existente) {
            $ids[$c["clave"]] = (int)$existente['id_comentario'];
            continue;
        }

        $idPadre = null;
        if ($c["padre"] !== null) {
            if (!isset($ids[$c["padre"]])) {
                throw new Exception("El comentario padre '{$c["padre"]}' debe definirse antes que '{$c["clave"]}'.");
            }
            $idPadre = $ids[$c["padre"]];
        }

        $titulo = $c["titulo"];
        $fecha = $c["fecha"];
        $bloquear = $c["bloquear"];

        $stmtInsertar->bind_param("sssiiii", $titulo, $fecha, $mensaje, $idUsuario, $idPublicacion, $idPadre, $bloquear);
        $stmtInsertar->execute();
        $ids[$c["clave"]] = $conn->insert_id;
    }

    $stmtUsuario->close();
    $stmtPub->close();
    $stmtExiste->close();
    $stmtInsertar->close();

    echo "Comentarios insertados correctamente.\n";
}