<?php

    class NotificacionController implements Controlador
    {
        private const LIMITE_POR_DEFECTO = 15;
        private const LIMITE_MAXIMO = 50;

        public function __construct(
            private Notificacion $modelo
        ) {}

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== CONTROL DE METODOS ==========
        // GET   /notificaciones              lista (limite, antes_de, solo_no_leidas)
        // GET   /notificaciones/contador     cantidad de no leidas
        // POST  /notificaciones/leer-todas   marca todas como leidas
        // PATCH /notificaciones/{id}         marca una como leida
        public function control(string $metodo, array $partes): void
        {
            $sub = $partes[1] ?? null;

            if ($sub === null) {
                match ($metodo) {
                    'GET'   => $this->listar(),
                    default => noPermitido(),
                };
                return;
            }

            if (isset($partes[2])) {
                noEncontrado();
                return;
            }

            if ($sub === 'contador') {
                match ($metodo) {
                    'GET'   => $this->contador(),
                    default => noPermitido(),
                };
                return;
            }

            if ($sub === 'leer-todas') {
                match ($metodo) {
                    'POST'  => $this->leerTodas(),
                    default => noPermitido(),
                };
                return;
            }

            $id = validarId($sub, 'ID de notificación inválido');
            match ($metodo) {
                'PATCH' => $this->marcarLeida($id),
                default => noPermitido(),
            };
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== GET ==========

        public function listar(): void
        {
            $idUsuario = usuarioActual();

            $limite = self::LIMITE_POR_DEFECTO;
            if (isset($_GET['limite'])) {
                $valor = trim((string)$_GET['limite']);
                if (!ctype_digit($valor) || (int)$valor < 1 || (int)$valor > self::LIMITE_MAXIMO) {
                    respuesta(400, ['success' => false, 'mensaje' => 'Límite inválido (1 a ' . self::LIMITE_MAXIMO . ')']);
                }
                $limite = (int)$valor;
            }

            $antesDe = null;
            if (isset($_GET['antes_de'])) {
                $valor = trim((string)$_GET['antes_de']);
                if (!ctype_digit($valor) || (int)$valor < 1) {
                    respuesta(400, ['success' => false, 'mensaje' => 'Cursor inválido']);
                }
                $antesDe = (int)$valor;
            }

            $soloNoLeidas = ($_GET['solo_no_leidas'] ?? '') === '1';

            //Se pide una de mas para saber si hay otra pagina
            $filas = $this->modelo->listar($idUsuario, $limite + 1, $antesDe, $soloNoLeidas);
            $hayMas = count($filas) > $limite;
            if ($hayMas) {
                array_pop($filas);
            }

            respuesta(200, [
                'success'          => true,
                'notificaciones'   => $filas,
                'hay_mas'          => $hayMas,
                'siguiente_cursor' => $hayMas ? end($filas)['id_notificacion'] : null,
                'no_leidas'        => $this->modelo->contarNoLeidas($idUsuario),
            ]);
        }

        public function contador(): void
        {
            respuesta(200, [
                'success'   => true,
                'no_leidas' => $this->modelo->contarNoLeidas(usuarioActual()),
            ]);
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== POST / PATCH ==========

        public function leerTodas(): void
        {
            $this->modelo->marcarTodasLeidas(usuarioActual());
            respuesta(200, ['success' => true, 'mensaje' => 'Notificaciones marcadas como leídas', 'no_leidas' => 0]);
        }

        public function marcarLeida(int $id): void
        {
            $idUsuario = usuarioActual();

            if (!$this->modelo->marcarLeida($id, $idUsuario)) {
                respuesta(404, ['success' => false, 'mensaje' => 'Notificación no encontrada']);
            }

            respuesta(200, [
                'success'   => true,
                'mensaje'   => 'Notificación marcada como leída',
                'no_leidas' => $this->modelo->contarNoLeidas($idUsuario),
            ]);
        }
    }