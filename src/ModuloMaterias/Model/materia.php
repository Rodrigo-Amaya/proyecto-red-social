<?php
    class Materia {
        private mysqli $conn;

        public function __construct($dbConnection){
            $this->conn = $dbConnection;
        }

        //========== GET ==========

        /**
         * Devuelve todas las materias para el filtro
         * @return array Lista de materias
         */
        public function todasMaterias(): array {
            $sql = "SELECT id_materia, nombre FROM materias ORDER BY nombre ASC";
            $resultado = $this->conn->query($sql);
            $filas = $resultado->fetch_all(MYSQLI_ASSOC);

            //valida id entero
            foreach ($filas as &$fila) {
                $fila['id_materia'] = (int)$fila['id_materia'];
            }
            unset($fila);

            return $filas;
        }
    }
?>