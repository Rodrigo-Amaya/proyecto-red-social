<?php
    class NivelEstudio {
        private mysqli $conn;

        public function __construct($dbConnection){
            $this->conn = $dbConnection;
        }

        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //========== GET ==========

        //Todos los niveles (para el select del registro)
        public function todosNiveles(): array {
            $sql = "SELECT id_nivel, nombre FROM nivel_estudio ORDER BY id_nivel ASC";
            $filas = $this->conn->query($sql)->fetch_all(MYSQLI_ASSOC);

            //id como entero, igual que en materias y en el feed
            foreach ($filas as &$fila) {
                $fila['id_nivel'] = (int)$fila['id_nivel'];
            }
            unset($fila);

            return $filas;
        }
    }
?>