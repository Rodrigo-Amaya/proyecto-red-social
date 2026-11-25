<?php

    class MateriaController implements Controlador
        {
            public function __construct(
                private Materia $modelo
            ) {}

            //========== CONTROL DE METODOS ==========
            public function control(string $metodo, array $partes): void
            {
                // url: /materias
                if (isset($partes[1])) {
                    noEncontrado();
                }

                match ($metodo) {
                    'GET'   => $this->listarMaterias(),
                    default => noPermitido(),
                };
            }

            //========== GET ==========
            function listarMaterias(): void {
                respuesta(200, [
                    'success' => true,
                    'mensaje' => 'Materias obtenidas correctamente',
                    'materias' => $this->modelo->todasMaterias()
                ]);
            }
        }
?>