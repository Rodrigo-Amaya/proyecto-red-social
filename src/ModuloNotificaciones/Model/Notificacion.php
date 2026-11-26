<?php
    class Notificacion {
        private mysqli $conn;

        //Ajusta este ID al rol de administrador de tu tabla `roles`
        private const ROL_ADMIN = 3;

        public const TIPO_COMENTARIO = 'COMENTARIO';
        public const TIPO_LIKE_COMENTARIO = 'LIKE_COMENTARIO';
        public const TIPO_NUEVA_SOLICITUD = 'NUEVA_SOLICITUD';

        public function __construct($dbConnection){
            $this->conn = $dbConnection;
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== GET ==========

        //Notificaciones del usuario, de la mas nueva a la mas antigua (paginadas por id)
        public function listar(int $idUsuario, int $limite, ?int $antesDe, bool $soloNoLeidas): array {
            $sql = "SELECT n.id_notificacion, n.tipo, n.leida, n.fecha, n.id_referencia, n.id_publicacion,
                    n.id_actor, a.nombre AS actor, a.url_foto AS actor_foto, p.titulo AS publicacion_titulo
                FROM notificaciones n
                JOIN usuarios a ON a.id_usuario = n.id_actor
                LEFT JOIN publicaciones p ON p.id_publicacion = n.id_publicacion
                WHERE n.id_usuario = ?";
            $tipos = "i";
            $parametros = [$idUsuario];

            if ($antesDe !== null) {
                $sql .= " AND n.id_notificacion < ?";
                $tipos .= "i";
                $parametros[] = $antesDe;
            }
            if ($soloNoLeidas) {
                $sql .= " AND n.leida = 0";
            }

            $sql .= " ORDER BY n.id_notificacion DESC LIMIT ?";
            $tipos .= "i";
            $parametros[] = $limite;

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param($tipos, ...$parametros);
            $stmt->execute();
            $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            foreach ($filas as &$fila) {
                $fila['id_notificacion'] = (int)$fila['id_notificacion'];
                $fila['leida'] = (int)$fila['leida'] === 1;
                $fila['id_referencia'] = (int)$fila['id_referencia'];
                $fila['id_publicacion'] = $fila['id_publicacion'] !== null ? (int)$fila['id_publicacion'] : null;
                $fila['id_actor'] = (int)$fila['id_actor'];
            }
            unset($fila);

            return $filas;
        }

        public function contarNoLeidas(int $idUsuario): int {
            $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM notificaciones WHERE id_usuario = ? AND leida = 0");
            $stmt->bind_param("i", $idUsuario);
            $stmt->execute();
            $total = (int)$stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();
            return $total;
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== PATCH ==========

        //true = existe (queda leida) | false = no existe o no es del usuario
        public function marcarLeida(int $id, int $idUsuario): bool {
            $stmt = $this->conn->prepare("SELECT id_notificacion FROM notificaciones WHERE id_notificacion = ? AND id_usuario = ?");
            $stmt->bind_param("ii", $id, $idUsuario);
            $stmt->execute();
            $existe = $stmt->get_result()->num_rows > 0;
            $stmt->close();

            if (!$existe) {
                return false;
            }

            $stmt = $this->conn->prepare("UPDATE notificaciones SET leida = 1 WHERE id_notificacion = ? AND id_usuario = ?");
            $stmt->bind_param("ii", $id, $idUsuario);
            $stmt->execute();
            $stmt->close();
            return true;
        }

        public function marcarTodasLeidas(int $idUsuario): int {
            $stmt = $this->conn->prepare("UPDATE notificaciones SET leida = 1 WHERE id_usuario = ? AND leida = 0");
            $stmt->bind_param("i", $idUsuario);
            $stmt->execute();
            $afectadas = $stmt->affected_rows;
            $stmt->close();
            return $afectadas;
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== CREACION (la llaman los modelos de comentarios, likes y aplicaciones) ==========
        //Son de "mejor esfuerzo": si fallan, no deben romper la accion original (usar dentro de try/catch).

        private function fechaActual(): string {
            return (new DateTime('now', new DateTimeZone('America/El_Salvador')))->format('Y-m-d H:i:s');
        }

        //Alguien comento en una publicacion: avisa al autor de la publicacion
        public function notificarComentario(int $idComentario, int $idPublicacion, int $idActor): void {
            $fecha = $this->fechaActual();
            $sql = "INSERT INTO notificaciones (id_usuario, id_actor, tipo, id_referencia, id_publicacion, fecha)
                SELECT p.id_usuario, ?, 'COMENTARIO', ?, p.id_publicacion, ?
                FROM publicaciones p
                JOIN usuarios u ON u.id_usuario = p.id_usuario
                WHERE p.id_publicacion = ? AND p.id_usuario <> ? AND u.bloqueo_usuario = 0
                ON DUPLICATE KEY UPDATE id_notificacion = id_notificacion";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iisii", $idActor, $idComentario, $fecha, $idPublicacion, $idActor);
            $stmt->execute();
            $stmt->close();
        }

        //Alguien dio like a un comentario: avisa al autor del comentario
        public function notificarLikeComentario(int $idComentario, int $idActor): void {
            $fecha = $this->fechaActual();
            $sql = "INSERT INTO notificaciones (id_usuario, id_actor, tipo, id_referencia, id_publicacion, fecha)
                SELECT c.id_usuario, ?, 'LIKE_COMENTARIO', c.id_comentario, c.id_publicacion, ?
                FROM comentarios c
                JOIN usuarios u ON u.id_usuario = c.id_usuario
                WHERE c.id_comentario = ? AND c.id_usuario <> ? AND u.bloqueo_usuario = 0
                ON DUPLICATE KEY UPDATE id_notificacion = id_notificacion";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("isii", $idActor, $fecha, $idComentario, $idActor);
            $stmt->execute();
            $stmt->close();
        }

        //Se quito el like: se retira la notificacion
        public function retirarLikeComentario(int $idComentario, int $idActor): void {
            $stmt = $this->conn->prepare("DELETE FROM notificaciones WHERE tipo = 'LIKE_COMENTARIO' AND id_referencia = ? AND id_actor = ?");
            $stmt->bind_param("ii", $idComentario, $idActor);
            $stmt->execute();
            $stmt->close();
        }

        //Nueva solicitud de profesor: avisa a todos los administradores activos
        public function notificarNuevaSolicitud(int $idAplicacion, int $idActor): void {
            $fecha = $this->fechaActual();
            $rolAdmin = self::ROL_ADMIN;
            $sql = "INSERT INTO notificaciones (id_usuario, id_actor, tipo, id_referencia, id_publicacion, fecha)
                SELECT u.id_usuario, ?, 'NUEVA_SOLICITUD', ?, NULL, ?
                FROM usuarios u
                WHERE u.id_rol = ? AND u.bloqueo_usuario = 0 AND u.id_usuario <> ?
                ON DUPLICATE KEY UPDATE id_notificacion = id_notificacion";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iisii", $idActor, $idAplicacion, $fecha, $rolAdmin, $idActor);
            $stmt->execute();
            $stmt->close();
        }

        //La solicitud se cancelo: se retira el aviso a los administradores
        public function retirarNuevaSolicitud(int $idAplicacion): void {
            $stmt = $this->conn->prepare("DELETE FROM notificaciones WHERE tipo = 'NUEVA_SOLICITUD' AND id_referencia = ?");
            $stmt->bind_param("i", $idAplicacion);
            $stmt->execute();
            $stmt->close();
        }
    }