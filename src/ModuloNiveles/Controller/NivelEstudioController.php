<?php

    class NivelEstudioController implements Controlador
        {
            public function __construct(
                private NivelEstudio $modelo
            ) {}

            //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //========== CONTROL DE METODOS ==========
            public function control(string $metodo, array $partes): void
            {
                // url: /niveles
                if (isset($partes[1])) {
                    noEncontrado();
                }

                match ($metodo) {
                    'GET'   => $this->listarNiveles(),
                    default => noPermitido(),
                };
            }

            //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //========== GET ==========
            function listarNiveles(): void {
                respuesta(200, [
                    'success' => true,
                    'mensaje' => 'Niveles de estudio obtenidos correctamente',
                    'niveles' => $this->modelo->todosNiveles()
                ]);
            }
        }
?>