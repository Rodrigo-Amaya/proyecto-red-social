-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 13-11-2026 a las 15:41:43
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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `aplicacion_profesor`
--

CREATE TABLE `aplicacion_profesor` (
  `id_aplicacion` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `universidad` varchar(200) NOT NULL,
  `telefono_universidad` varchar(30) DEFAULT NULL,
  `carrera` varchar(150) NOT NULL,
  `grado_academico` varchar(50) NOT NULL,
  `anio_graducacion` year(4) NOT NULL,
  `url_titulo` varchar(500) NOT NULL,
  `estado` varchar(30) NOT NULL DEFAULT 'PENDIENTE',
  `fecha_aplicacion` datetime NOT NULL DEFAULT current_timestamp(),
  `primera_revision_fecha` datetime NOT NULL,
  `segunda_revision_fecha` datetime DEFAULT NULL,
  `razon_rechazo` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `archivos_comentarios`
--

CREATE TABLE `archivos_comentarios` (
  `id_archivo` int(11) NOT NULL,
  `id_comentario` int(11) NOT NULL,
  `url_archivo` varchar(255) NOT NULL,
  `nombre_archivo` varchar(255) DEFAULT NULL,
  `tipo_archivo` varchar(100) DEFAULT NULL,
  `fecha_subida` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `archivos_publicaciones`
--

CREATE TABLE `archivos_publicaciones` (
  `id_archivo` int(11) NOT NULL,
  `id_publicacion` int(11) NOT NULL,
  `url_archivo` varchar(500) NOT NULL,
  `nombre_archivo` varchar(255) DEFAULT NULL,
  `tipo_archivo` varchar(100) DEFAULT NULL,
  `fecha_subida` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comentarios`
--

CREATE TABLE `comentarios` (
  `id_comentario` int(11) NOT NULL,
  `titulo` varchar(150) DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `mensaje` text NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_publicacion` int(11) NOT NULL,
  `id_comentario_padre` int(11) NOT NULL,
  `bloquear` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `likes_comentario`
--

CREATE TABLE `likes_comentario` (
  `id_usuario` int(11) NOT NULL,
  `id_comentario` int(11) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `likes_publicacion`
--

CREATE TABLE `likes_publicacion` (
  `id_usuario` int(11) NOT NULL,
  `id_publicacion` int(11) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materias`
--

CREATE TABLE `materias` (
  `id_materia` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `nivel_estudio`
--

CREATE TABLE `nivel_estudio` (
  `id_nivel` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `publicaciones`
--

CREATE TABLE `publicaciones` (
  `id_publicacion` int(11) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `mensaje` text NOT NULL,
  `puntos` int(11) NOT NULL DEFAULT 0,
  `id_usuario` int(11) NOT NULL,
  `id_materia` int(11) NOT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'SINRESOLVER',
  `bloquear` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `publicaciones_guardadas`
--

CREATE TABLE `publicaciones_guardadas` (
  `id_guardado` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_publicacion` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `revision_aplicacion_profesor`
--

CREATE TABLE `revision_aplicacion_profesor` (
  `id_revision` int(11) NOT NULL,
  `id_aplicacion` int(11) NOT NULL,
  `id_administrador` int(11) NOT NULL,
  `estapa_revision` tinyint(4) NOT NULL,
  `decision` varchar(20) NOT NULL,
  `comentario` text DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `token_reseteo`
--

CREATE TABLE `token_reseteo` (
  `id_token` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `fecha_expiracion` datetime NOT NULL,
  `validacion` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(150) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `id_rol` int(11) NOT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `puntos` int(11) NOT NULL DEFAULT 0,
  `id_nivel` int(11) NOT NULL,
  `url_foto` varchar(500) NOT NULL,
  `bloqueo_usuario` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `aplicacion_profesor`
--
ALTER TABLE `aplicacion_profesor`
  ADD PRIMARY KEY (`id_aplicacion`),
  ADD KEY `profesor_usuario_fk` (`id_usuario`);

--
-- Indices de la tabla `archivos_comentarios`
--
ALTER TABLE `archivos_comentarios`
  ADD PRIMARY KEY (`id_archivo`),
  ADD KEY `archivo_comentario_fk` (`id_comentario`);

--
-- Indices de la tabla `archivos_publicaciones`
--
ALTER TABLE `archivos_publicaciones`
  ADD PRIMARY KEY (`id_archivo`),
  ADD KEY `archivos_publicacion_fk` (`id_publicacion`);

--
-- Indices de la tabla `comentarios`
--
ALTER TABLE `comentarios`
  ADD PRIMARY KEY (`id_comentario`),
  ADD KEY `comentario_usuario_fk` (`id_usuario`),
  ADD KEY `comentario_publicacion_fk` (`id_publicacion`),
  ADD KEY `comentario_comentario_fk` (`id_comentario_padre`);

--
-- Indices de la tabla `likes_comentario`
--
ALTER TABLE `likes_comentario`
  ADD PRIMARY KEY (`id_usuario`,`id_comentario`),
  ADD KEY `likes_comentario_fk` (`id_comentario`);

--
-- Indices de la tabla `likes_publicacion`
--
ALTER TABLE `likes_publicacion`
  ADD PRIMARY KEY (`id_usuario`,`id_publicacion`),
  ADD KEY `likes_publicacion_fk` (`id_publicacion`);

--
-- Indices de la tabla `materias`
--
ALTER TABLE `materias`
  ADD PRIMARY KEY (`id_materia`),
  ADD UNIQUE KEY `nombre_materi_unico` (`nombre`);

--
-- Indices de la tabla `nivel_estudio`
--
ALTER TABLE `nivel_estudio`
  ADD PRIMARY KEY (`id_nivel`),
  ADD UNIQUE KEY `nombre_nivel_unico` (`nombre`);

--
-- Indices de la tabla `publicaciones`
--
ALTER TABLE `publicaciones`
  ADD PRIMARY KEY (`id_publicacion`),
  ADD KEY `publicacion_usuario_fk` (`id_usuario`),
  ADD KEY `publicacion_materia` (`id_materia`);

--
-- Indices de la tabla `publicaciones_guardadas`
--
ALTER TABLE `publicaciones_guardadas`
  ADD PRIMARY KEY (`id_guardado`),
  ADD UNIQUE KEY `id_usuario` (`id_usuario`,`id_publicacion`),
  ADD KEY `guardado_publicacion_fk` (`id_publicacion`);

--
-- Indices de la tabla `revision_aplicacion_profesor`
--
ALTER TABLE `revision_aplicacion_profesor`
  ADD PRIMARY KEY (`id_revision`),
  ADD KEY `revision_aplicacion_fk` (`id_aplicacion`),
  ADD KEY `revision_usuario_fk` (`id_administrador`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`),
  ADD UNIQUE KEY `nombre_rol_unico` (`nombre`);

--
-- Indices de la tabla `token_reseteo`
--
ALTER TABLE `token_reseteo`
  ADD PRIMARY KEY (`id_token`),
  ADD KEY `usuario_token_fk` (`id_usuario`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `correo_usuario` (`correo`),
  ADD KEY `rol_usuario_fk` (`id_rol`),
  ADD KEY `nivel_usuario_fk` (`id_nivel`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `aplicacion_profesor`
--
ALTER TABLE `aplicacion_profesor`
  MODIFY `id_aplicacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `archivos_comentarios`
--
ALTER TABLE `archivos_comentarios`
  MODIFY `id_archivo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `archivos_publicaciones`
--
ALTER TABLE `archivos_publicaciones`
  MODIFY `id_archivo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `comentarios`
--
ALTER TABLE `comentarios`
  MODIFY `id_comentario` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `materias`
--
ALTER TABLE `materias`
  MODIFY `id_materia` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `nivel_estudio`
--
ALTER TABLE `nivel_estudio`
  MODIFY `id_nivel` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `publicaciones`
--
ALTER TABLE `publicaciones`
  MODIFY `id_publicacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `publicaciones_guardadas`
--
ALTER TABLE `publicaciones_guardadas`
  MODIFY `id_guardado` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `revision_aplicacion_profesor`
--
ALTER TABLE `revision_aplicacion_profesor`
  MODIFY `id_revision` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `token_reseteo`
--
ALTER TABLE `token_reseteo`
  MODIFY `id_token` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `aplicacion_profesor`
--
ALTER TABLE `aplicacion_profesor`
  ADD CONSTRAINT `profesor_usuario_fk` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `archivos_comentarios`
--
ALTER TABLE `archivos_comentarios`
  ADD CONSTRAINT `archivo_comentario_fk` FOREIGN KEY (`id_comentario`) REFERENCES `comentarios` (`id_comentario`);

--
-- Filtros para la tabla `archivos_publicaciones`
--
ALTER TABLE `archivos_publicaciones`
  ADD CONSTRAINT `archivos_publicacion_fk` FOREIGN KEY (`id_publicacion`) REFERENCES `publicaciones` (`id_publicacion`);

--
-- Filtros para la tabla `comentarios`
--
ALTER TABLE `comentarios`
  ADD CONSTRAINT `comentario_comentario_fk` FOREIGN KEY (`id_comentario_padre`) REFERENCES `comentarios` (`id_comentario`),
  ADD CONSTRAINT `comentario_publicacion_fk` FOREIGN KEY (`id_publicacion`) REFERENCES `publicaciones` (`id_publicacion`),
  ADD CONSTRAINT `comentario_usuario_fk` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `likes_comentario`
--
ALTER TABLE `likes_comentario`
  ADD CONSTRAINT `likes_comentario_fk` FOREIGN KEY (`id_comentario`) REFERENCES `comentarios` (`id_comentario`),
  ADD CONSTRAINT `likes_usuario_fk` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `likes_publicacion`
--
ALTER TABLE `likes_publicacion`
  ADD CONSTRAINT `likes_publicacion_fk` FOREIGN KEY (`id_publicacion`) REFERENCES `publicaciones` (`id_publicacion`),
  ADD CONSTRAINT `likes_publicacion_usuario_fk` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `publicaciones`
--
ALTER TABLE `publicaciones`
  ADD CONSTRAINT `publicacion_materia` FOREIGN KEY (`id_materia`) REFERENCES `materias` (`id_materia`),
  ADD CONSTRAINT `publicacion_usuario_fk` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `publicaciones_guardadas`
--
ALTER TABLE `publicaciones_guardadas`
  ADD CONSTRAINT `guardado_publicacion_fk` FOREIGN KEY (`id_publicacion`) REFERENCES `publicaciones` (`id_publicacion`),
  ADD CONSTRAINT `guardado_usuario_fk` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `revision_aplicacion_profesor`
--
ALTER TABLE `revision_aplicacion_profesor`
  ADD CONSTRAINT `revision_aplicacion_fk` FOREIGN KEY (`id_aplicacion`) REFERENCES `aplicacion_profesor` (`id_aplicacion`),
  ADD CONSTRAINT `revision_usuario_fk` FOREIGN KEY (`id_administrador`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `token_reseteo`
--
ALTER TABLE `token_reseteo`
  ADD CONSTRAINT `usuario_token_fk` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `nivel_usuario_fk` FOREIGN KEY (`id_nivel`) REFERENCES `nivel_estudio` (`id_nivel`),
  ADD CONSTRAINT `rol_usuario_fk` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
