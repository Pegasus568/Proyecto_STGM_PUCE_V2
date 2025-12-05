-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3307
-- Tiempo de generación: 04-12-2025 a las 19:44:15
-- Versión del servidor: 9.3.0
-- Versión de PHP: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sgtm_puce`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportes`
--

CREATE TABLE `reportes` (
  `id` int NOT NULL,
  `tutoria_id` int DEFAULT NULL,
  `tipo` enum('ACTA','OBSERVACION','REPORTE') NOT NULL DEFAULT 'OBSERVACION',
  `titulo` varchar(200) NOT NULL,
  `contenido` text NOT NULL,
  `creado_por` int NOT NULL,
  `tutor_id` int DEFAULT NULL,
  `estudiante_id` int DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `privado` tinyint(1) NOT NULL DEFAULT '0',
  `adjunto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tutorias`
--

CREATE TABLE `tutorias` (
  `id` int NOT NULL,
  `titulo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `fecha` date DEFAULT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fin` time DEFAULT NULL,
  `modalidad` enum('PRESENCIAL','VIRTUAL','HIBRIDA') COLLATE utf8mb4_unicode_ci DEFAULT 'PRESENCIAL',
  `lugar` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('PROGRAMADA','REALIZADA','CANCELADA') COLLATE utf8mb4_unicode_ci DEFAULT 'PROGRAMADA',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `id_tutor` int DEFAULT NULL,
  `id_estudiante` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cedula` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `carrera` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ciclo` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `correo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` enum('ADMIN','DOCENTE','ESTUDIANTE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ESTUDIANTE',
  `estado` enum('ACTIVO','INACTIVO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVO',
  `ultimo_login` datetime DEFAULT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `cedula`, `telefono`, `carrera`, `ciclo`, `correo`, `rol`, `estado`, `ultimo_login`, `password_hash`, `creado_en`) VALUES
(1, 'Admin SGTM', NULL, NULL, NULL, NULL, 'admin@pucesa.edu.ec', 'ADMIN', 'ACTIVO', '2025-12-03 09:27:59', '$2b$12$6yY/oWBUFtLTNTVtLrQcwOAF3IyHWb9Hb3fz1u2BQKCuQEmdUt3gC', '2025-11-17 04:09:53'),
(3, 'Pablo Emilio Escobar Gaviria', '1850837301', '0999999999', 'Gastronomia', '1ro', 'peescobar@pucesa.edu.ec', 'DOCENTE', 'ACTIVO', '2025-11-26 09:21:34', '$2y$10$iRWmngNh.cErlYk7Ce2iSO/s3YOVU6BWCmWAEh6AhckrIqrVO7MYe', '2025-11-24 02:15:11'),
(4, 'Joaquín Archivaldo Guzmán Loera', '1850837300', '0999999999', 'Gastronomia', '1ro', 'jaguzman@pucesa.edu.ec', 'ESTUDIANTE', 'ACTIVO', '2025-12-03 09:21:31', '$2y$10$wqpYSQ.dKYXfNjE8AAVOQ.7Hxn/4srarKJkKnHxrSnuGngPerI90W', '2025-11-24 02:52:15');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `reportes`
--
ALTER TABLE `reportes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tutoria_id` (`tutoria_id`),
  ADD KEY `creado_por` (`creado_por`),
  ADD KEY `tutor_id` (`tutor_id`),
  ADD KEY `estudiante_id` (`estudiante_id`),
  ADD KEY `fecha_creacion` (`fecha_creacion`);

--
-- Indices de la tabla `tutorias`
--
ALTER TABLE `tutorias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_tutor` (`id_tutor`),
  ADD KEY `id_estudiante` (`id_estudiante`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `reportes`
--
ALTER TABLE `reportes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `tutorias`
--
ALTER TABLE `tutorias`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `reportes`
--
ALTER TABLE `reportes`
  ADD CONSTRAINT `reportes_ibfk_1` FOREIGN KEY (`tutoria_id`) REFERENCES `tutorias` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reportes_ibfk_2` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tutorias`
--
ALTER TABLE `tutorias`
  ADD CONSTRAINT `tutorias_ibfk_1` FOREIGN KEY (`id_tutor`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `tutorias_ibfk_2` FOREIGN KEY (`id_estudiante`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
