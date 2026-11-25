<?php

function seedMaterias(mysqli $conn): void
{
    $materias = [
        ["nombre" => 'Matematicas', "descripcion" => 'Aritmetica, algebra, geometria, trigonometria y calculo.'],
        ["nombre" => 'Lenguaje y Literatura', "descripcion" => 'Gramatica, ortografia, redaccion, lectura y literatura.'],
        ["nombre" => 'Ciencias Sociales', "descripcion" => 'Historia, geografia, civica y estudios sociales.'],
        ["nombre" => 'Ingles', "descripcion" => 'Gramatica, vocabulario, comprension y comunicacion en ingles.'],
        ["nombre" => 'Computacion', "descripcion" => 'Informatica, ofimatica, hardware y software.'],
        ["nombre" => 'Ciencias Naturales', "descripcion" => 'Biologia, fisica, quimica y ciencias generales.'],
        ["nombre" => 'Biologia', "descripcion" => 'Estudio de los seres vivos y sus procesos biologicos.'],
        ["nombre" => 'Quimica', "descripcion" => 'Composicion, propiedades y transformaciones de la materia.'],
        ["nombre" => 'Fisica', "descripcion" => 'Estudio de la materia, energia y sus interacciones.'],
        ["nombre" => 'Estadistica', "descripcion" => 'Analisis de datos, probabilidad y estadistica aplicada.'],
        ["nombre" => 'Geometria', "descripcion" => 'Estudio de figuras, dimensiones y propiedades geometricas.'],
        ["nombre" => 'Algebra', "descripcion" => 'Expresiones algebraicas, ecuaciones y funciones.'],
        ["nombre" => 'Calculo', "descripcion" => 'Calculo diferencial, integral y multivariable.'],
        ["nombre" => 'Programacion', "descripcion" => 'Logica de programacion, algoritmos y desarrollo de software.'],
        ["nombre" => 'Bases de Datos', "descripcion" => 'Modelado, diseño, consultas y administracion de bases de datos.'],
        ["nombre" => 'Redes Informaticas', "descripcion" => 'Arquitectura, configuracion y administracion de redes.'],
        ["nombre" => 'Desarrollo Web', "descripcion" => 'Desarrollo frontend, backend y aplicaciones web.'],
        ["nombre" => 'Inteligencia Artificial', "descripcion" => 'Aprendizaje automatico, modelos y aplicaciones de IA.'],
        ["nombre" => 'Ciberseguridad', "descripcion" => 'Seguridad informatica, proteccion de datos y redes.'],
        ["nombre" => 'Electronica', "descripcion" => 'Circuitos, componentes y sistemas electronicos.'],
        ["nombre" => 'Contabilidad', "descripcion" => 'Registro, clasificacion y analisis de operaciones financieras.'],
        ["nombre" => 'Administracion de Empresas', "descripcion" => 'Gestion, organizacion y direccion empresarial.'],
        ["nombre" => 'Economia', "descripcion" => 'Produccion, distribucion y consumo de bienes y servicios.'],
        ["nombre" => 'Emprendimiento', "descripcion" => 'Creacion, gestion y desarrollo de negocios.'],
        ["nombre" => 'Educacion Financiera', "descripcion" => 'Ahorro, presupuesto, inversion y finanzas personales.'],
        ["nombre" => 'Filosofia', "descripcion" => 'Pensamiento critico, etica y corrientes filosoficas.'],
        ["nombre" => 'Psicologia', "descripcion" => 'Estudio del comportamiento y los procesos mentales.'],
        ["nombre" => 'Arte', "descripcion" => 'Expresion artistica, dibujo, pintura y creatividad.'],
        ["nombre" => 'Musica', "descripcion" => 'Teoria musical, instrumentos y composicion.'],
        ["nombre" => 'Educacion Fisica', "descripcion" => 'Actividad fisica, deportes y salud corporal.'],
        ["nombre" => 'Educacion Ambiental', "descripcion" => 'Ecologia, sostenibilidad y conservacion del medio ambiente.'],
        ["nombre" => 'Geografia', "descripcion" => 'Estudio del territorio, clima y distribucion espacial.'],
        ["nombre" => 'Historia', "descripcion" => 'Estudio de acontecimientos y procesos historicos.'],
        ["nombre" => 'Derecho', "descripcion" => 'Normativa juridica, legislacion y ciencias legales.'],
        ["nombre" => 'Medicina', "descripcion" => 'Ciencias medicas y fundamentos de la salud.'],
        ["nombre" => 'Enfermeria', "descripcion" => 'Cuidados de salud y atencion al paciente.'],
        ["nombre" => 'Ingenieria', "descripcion" => 'Fundamentos y aplicaciones de las distintas ramas de ingenieria.'],
        ["nombre" => 'Otros', "descripcion" => 'Materias y disciplinas que no se encuentran en las categorias anteriores.']
    ];

    $sql = "INSERT INTO materias (nombre, descripcion)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion)";

    $stmt = $conn->prepare($sql);

    foreach ($materias as $materia) {
        $nombre = $materia["nombre"];
        $descripcion = $materia["descripcion"];
        $stmt->bind_param("ss", $nombre, $descripcion);
        $stmt->execute();
    }
    $stmt->close();

    echo "Materias insertadas correctamente.\n";
}