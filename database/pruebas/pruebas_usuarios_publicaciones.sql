-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 13-11-2026 a las 15:43:15
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `red_social`
--

--
-- Volcado de datos para la tabla `materias`
--

INSERT INTO `materias` (`id_materia`, `nombre`, `descripcion`) VALUES
(1, 'Matematicas', 'Aritmetica, algebra, geometria, trigonometria y calculo.'),
(2, 'Lenguaje y Literatura', 'Gramatica, ortografia, redaccion, lectura y literatura.'),
(3, 'Ciencias Sociales', 'Historia, geografia, civica y estudios sociales.'),
(4, 'Ingles', 'Gramatica, vocabulario, comprension y comunicacion en ingles.'),
(5, 'Computacion', 'Informatica, ofimatica, hardware y software.'),
(6, 'Ciencias Naturales', 'Biologia, fisica, quimica y ciencias generales.'),
(7, 'Biologia', 'Estudio de los seres vivos y sus procesos biologicos.'),
(8, 'Quimica', 'Composicion, propiedades y transformaciones de la materia.'),
(9, 'Fisica', 'Estudio de la materia, energia y sus interacciones.'),
(10, 'Estadistica', 'Analisis de datos, probabilidad y estadistica aplicada.'),
(11, 'Geometria', 'Estudio de figuras, dimensiones y propiedades geometricas.'),
(12, 'Algebra', 'Expresiones algebraicas, ecuaciones y funciones.'),
(13, 'Calculo', 'Calculo diferencial, integral y multivariable.'),
(14, 'Programacion', 'Logica de programacion, algoritmos y desarrollo de software.'),
(15, 'Bases de Datos', 'Modelado, diseño, consultas y administracion de bases de datos.'),
(16, 'Redes Informaticas', 'Arquitectura, configuracion y administracion de redes.'),
(17, 'Desarrollo Web', 'Desarrollo frontend, backend y aplicaciones web.'),
(18, 'Inteligencia Artificial', 'Aprendizaje automatico, modelos y aplicaciones de IA.'),
(19, 'Ciberseguridad', 'Seguridad informatica, proteccion de datos y redes.'),
(20, 'Electronica', 'Circuitos, componentes y sistemas electronicos.'),
(21, 'Contabilidad', 'Registro, clasificacion y analisis de operaciones financieras.'),
(22, 'Administracion de Empresas', 'Gestion, organizacion y direccion empresarial.'),
(23, 'Economia', 'Produccion, distribucion y consumo de bienes y servicios.'),
(24, 'Emprendimiento', 'Creacion, gestion y desarrollo de negocios.'),
(25, 'Educacion Financiera', 'Ahorro, presupuesto, inversion y finanzas personales.'),
(26, 'Filosofia', 'Pensamiento critico, etica y corrientes filosoficas.'),
(27, 'Psicologia', 'Estudio del comportamiento y los procesos mentales.'),
(28, 'Arte', 'Expresion artistica, dibujo, pintura y creatividad.'),
(29, 'Musica', 'Teoria musical, instrumentos y composicion.'),
(30, 'Educacion Fisica', 'Actividad fisica, deportes y salud corporal.'),
(31, 'Educacion Ambiental', 'Ecologia, sostenibilidad y conservacion del medio ambiente.'),
(32, 'Geografia', 'Estudio del territorio, clima y distribucion espacial.'),
(33, 'Historia', 'Estudio de acontecimientos y procesos historicos.'),
(34, 'Derecho', 'Normativa juridica, legislacion y ciencias legales.'),
(35, 'Medicina', 'Ciencias medicas y fundamentos de la salud.'),
(36, 'Enfermeria', 'Cuidados de salud y atencion al paciente.'),
(37, 'Ingenieria', 'Fundamentos y aplicaciones de las distintas ramas de ingenieria.'),
(38, 'Otros', 'Materias y disciplinas que no se encuentran en las categorias anteriores.');

--
-- Volcado de datos para la tabla `nivel_estudio`
--

INSERT INTO `nivel_estudio` (`id_nivel`, `nombre`) VALUES
(2, 'Bachillerato General'),
(3, 'Bachillerato Tecnico'),
(10, 'Doctorado'),
(1, 'Educacion Basica'),
(5, 'Educacion Universitaria'),
(6, 'Egresado Universitario'),
(8, 'Ingenieria'),
(7, 'Licenciatura'),
(9, 'Maestria'),
(11, 'Postdoctorado'),
(12, 'Profesional'),
(4, 'Tecnico Superior');

--
-- Volcado de datos para la tabla `publicaciones`
--

INSERT INTO `publicaciones` (`id_publicacion`, `titulo`, `fecha`, `mensaje`, `puntos`, `id_usuario`, `id_materia`, `estado`, `bloquear`) VALUES
(1, 'Ayuda con ecuaciones cuadráticas', '2026-11-01 17:39:36', 'Estoy teniendo dificultades para resolver ecuaciones cuadráticas utilizando la fórmula general. ¿Alguien podría explicarme el procedimiento paso a paso y compartir algunos ejercicios para practicar?', 0, 1, 12, 'SINRESOLVER', 1),
(2, 'Resumen de la Segunda Guerra Mundial', '2026-11-04 18:50:32', 'Estoy investigando los acontecimientos más importantes de la Segunda Guerra Mundial. Me gustaría conocer sus principales consecuencias políticas, económicas y sociales.', 0, 2, 3, 'SINRESOLVER', 0),
(3, 'Consejos para mejorar la redacción', '2026-11-06 18:55:57', '¿Qué técnicas recomiendan para mejorar la redacción de ensayos académicos? Estoy buscando consejos para organizar mejor mis ideas, utilizar conectores y evitar errores gramaticales.', 0, 3, 2, 'SINRESOLVER', 0),
(4, 'Aprendiendo programación en PHP', '2026-11-06 16:48:57', 'Estoy comenzando a desarrollar proyectos con PHP y MySQL. ¿Qué buenas prácticas recomiendan para organizar el código y separar las consultas de la base de datos de la lógica del sistema?', 0, 4, 14, 'SINRESOLVER', 0),
(5, 'Importancia de las energías renovables', '2026-11-06 15:16:23', 'Necesito investigar sobre las energías renovables y su impacto en el medio ambiente. ¿Cuáles consideran que son las principales ventajas de utilizar energía solar y eólica?', 0, 5, 6, 'SINRESOLVER', 0),
(6, 'Ejercicios de geometría', '2026-11-06 17:38:57', 'Estoy practicando el cálculo de áreas y volúmenes de figuras geométricas. ¿Alguien podría recomendarme ejercicios para comprender mejor las fórmulas y sus aplicaciones?', 0, 6, 11, 'SINRESOLVER', 0),
(7, 'Practicar inglés desde cero', '2026-11-06 23:58:32', 'Quiero mejorar mi nivel de inglés, especialmente mi vocabulario y pronunciación. ¿Qué aplicaciones, páginas o métodos gratuitos recomiendan para practicar todos los días?', 0, 7, 4, 'SINRESOLVER', 0),
(8, 'La economía de El Salvador', '2026-11-07 06:37:47', 'Estoy realizando una investigación sobre la economía de El Salvador. ¿Cuáles consideran que son los principales factores que influyen en el crecimiento económico del país?', 0, 8, 23, 'SINRESOLVER', 0),
(9, 'Diferencias entre HTML, CSS y JavaScript', '2026-11-07 13:21:50', 'Estoy aprendiendo desarrollo web y quisiera comprender mejor las funciones de HTML, CSS y JavaScript. ¿Podrían explicarme cómo trabajan juntos para construir una página web?', 0, 9, 17, 'SINRESOLVER', 1),
(10, 'Ayuda con medidas estadísticas', '2026-11-07 15:41:13', 'Estoy estudiando medidas de tendencia central. ¿Alguien podría explicarme las diferencias entre media, mediana y moda utilizando ejemplos prácticos?', 0, 10, 10, 'SINRESOLVER', 0),
(11, 'Aplicaciones de la inteligencia artificial', '2026-11-07 23:51:57', 'Me interesa conocer cómo se utiliza la inteligencia artificial en la educación. ¿Qué herramientas consideran útiles para facilitar el aprendizaje y cuáles son sus ventajas?', 0, 11, 18, 'RESUELTA', 0),
(12, 'Conceptos básicos de genética', '2026-11-08 05:23:57', 'Necesito comprender mejor las leyes de Mendel y cómo se transmiten los caracteres hereditarios. ¿Alguien podría compartir ejemplos sencillos de cruces genéticos?', 0, 12, 7, 'SINRESOLVER', 0),
(13, 'Importancia de la seguridad informática', '2026-11-08 09:23:57', 'Estoy investigando sobre seguridad informática. ¿Cuáles son las principales amenazas que pueden afectar a los usuarios y qué medidas recomiendan para proteger nuestros datos personales?', 0, 13, 19, 'SINRESOLVER', 0),
(14, 'Diseño de bases de datos relacionales', '2026-11-08 09:40:45', 'Estoy desarrollando un proyecto que utiliza MySQL y tengo dudas sobre cómo organizar correctamente las tablas, establecer claves primarias y relacionar los registros mediante claves foráneas.', 0, 14, 15, 'SINRESOLVER', 0),
(15, 'Consejos para administrar el dinero', '2026-11-08 10:24:10', 'Quiero aprender a administrar mejor mis finanzas personales. ¿Qué métodos recomiendan para organizar un presupuesto mensual, ahorrar y evitar gastos innecesarios?', 0, 15, 25, 'RESUELTA', 0);

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id_rol`, `nombre`) VALUES
(3, 'ADMINISTRADOR'),
(1, 'ESTUDIANTE'),
(2, 'PROFESOR');

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `correo`, `contrasena`, `id_rol`, `fecha_registro`, `puntos`, `id_nivel`, `url_foto`, `bloqueo_usuario`) VALUES
(1, 'Fernando', 'roFer@gmail.com', '$2y$10$YXyNk3Fi3xUtBV79ndUIqeCMzqERmCMEtBW2LAcFPf1cFGC.Y80zy', 1, '2026-11-01 09:01:28', 0, 8, 'imagenes/usuarios/fotoUsuario1.jpg', 0),
(2, 'Andres Lopez', 'Lopez@gmail.com', '$2y$10$704ofHk6XPjORkU.FjcyOO0qkkmyR1uztUArXtt3p1QtsE/TBXrTm', 2, '2026-11-04 18:16:54', 0, 5, 'imagenes/usuarios/fotoUsuario2.jpg', 0),
(3, 'Luicia', 'Lucia1@gmail.com', '$2y$10$t4BohpVGDUwF3TEnMhk2/eDS8WjQqbCLdFotC4fEGa1riSvgqelmK', 2, '2026-11-04 07:05:23', 0, 12, 'imagenes/usuarios/fotoUsuario3.jpg', 0),
(4, 'Felix', 'felix_perez@hotmail.com', '$2y$10$7RlxEwSLWMrCLOdlqCKDweq0yI9X2cYYD5CZQxATlb91sfZVicVSe', 3, '2026-11-05 14:11:05', 0, 7, 'imagenes/usuarios/fotoUsuario4.jpg', 0),
(5, 'Alfredo', 'alfred_r@hotmail.com', '$2y$10$5WYYVZ8zpmbyYlDC.87d9uUITbnO3wn7SL6dyYuXJ2FDcBPQDCwPy', 2, '2026-11-05 16:02:34', 0, 3, 'imagenes/usuarios/fotoUsuario5.jpg', 0),
(6, 'Gabriela', 'gab_br@hotmail.com', '$2y$10$CHRNKoPdK3RzS0XK4MTG3uOwvJjsZ8CqVyf/aKsMOpw3ruQXD.uBG', 1, '2026-11-05 19:17:22', 0, 6, 'imagenes/usuarios/fotoUsuario6.jpg', 0),
(7, 'Nicolas', 'frank_nicolas@gmail.com', '$2y$10$eMyHFen8xRy64WozuxoJy.PNbjWZaJIrV7DRn2M5U6pRphykw0QIC', 3, '2026-11-06 03:20:42', 0, 8, 'imagenes/usuarios/fotoUsuario7.jpg', 0),
(8, 'Argueta', 'argueta_@gmail.com', '$2y$10$xYOsAQVCblQYBFjx77Yw/.ZshlztShEo8hDFMrL.kjATY68mayzP.', 1, '2026-11-06 08:09:10', 0, 10, 'imagenes/usuarios/fotoUsuario8.jpg', 1),
(9, 'Camilo', 'camilo@gmail.com', '$2y$10$ztAj1.V.Q0XYOEoF8igOounX7hhuUNLZ5YkoY.jQlEWzzLthwY1sS', 1, '2026-11-06 15:25:54', 0, 1, 'imagenes/usuarios/fotoUsuario9.jpg', 0),
(10, 'Benito', 'bb_benito@gmail.com', '$2y$10$T0Du8KC9q8v28kP1sFVT7OnGg0Lp7z.uVRM7XhjdI7flBcfhhfokG', 3, '2026-11-07 08:44:33', 0, 10, 'imagenes/usuarios/fotoUsuario10.jpg', 0),
(11, 'Miranda', 'd_miranda@gmail.com', '$2y$10$BlCIM5alzITh1mtHSj3cC.XZJ0wiWn38UVLmwOg91E.KW4qJCWtou', 1, '2026-11-07 15:11:38', 0, 11, 'imagenes/usuarios/fotoUsuario11.jpg', 0),
(12, 'Daniel Fidel', 'd_Fidel@gmail.com', '$2y$10$pKpT9nG/1XxgBwUPRrdi/.naaXPTAXnjekqrLxQTaOSrdiUddR6uK', 1, '2026-11-07 21:28:38', 0, 7, 'imagenes/usuarios/fotoUsuario12.jpg', 1),
(13, 'Jose', 'Jose_Flores@gmail.com', '$2y$10$OteD4x8bZPC8qFLES1mMnOoiGI3YLHvtPRu6md7GNuoIUmcbxKaOa', 1, '2026-11-08 03:50:03', 0, 3, 'imagenes/usuarios/fotoUsuario13.jpg', 0),
(14, 'Juan Felipe', 'Juan_Felipe@gmail.com', '$2y$10$ep8z0xnKjjAdPUCt12koou4VZY8l5AZFTp3.CgshZHqtqbvUfvUoG', 2, '2026-11-08 06:19:19', 0, 2, 'imagenes/usuarios/fotoUsuario14.jpg', 0),
(15, 'Hernan', 'Hernan_c@gmail.com', '$2y$10$iPSoOlxZfm2/v8I2LCYuUOhkte7Kk8X0Ee24cK03AN86o5CHQsoeq', 2, '2026-11-08 09:14:01', 0, 4, 'imagenes/usuarios/fotoUsuario15.jpg', 0);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
