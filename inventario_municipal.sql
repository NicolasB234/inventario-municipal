-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 07-07-2025 a las 01:52:50
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
-- Base de datos: `inventario_municipal`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `area_id` varchar(100) DEFAULT NULL,
  `action_type` varchar(100) NOT NULL COMMENT 'Ej: item_added, item_deleted',
  `details` text NOT NULL COMMENT 'El mensaje de la notificación',
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `username`, `area_id`, `action_type`, `details`, `timestamp`) VALUES
(14, 17, 'nicolas', 'dir-atencion-primaria', 'item_added', 'Nuevo ítem \"un item\" añadido al área \"Despacho Médico Principal\".', '2025-06-29 21:12:14'),
(15, 17, 'nicolas', 'dir-atencion-primaria', 'item_transferred', 'Ítem \"un item (ID: 10)\" traspasado de \"Despacho Médico Principal\" a \"Intendencia Municipal > Secretaría de Gobierno > Dirección de Tránsito\". Motivo: se fue de viaje ', '2025-06-29 21:35:35'),
(16, 18, 'seba', 'dir-transito', 'item_deleted', 'Ítem \"un item\" eliminado del área \"Dirección de Tránsito\".', '2025-06-29 22:24:38'),
(17, 18, 'seba', 'dir-transito', 'item_added', 'Nuevo ítem \"item2\" añadido al área \"Dirección de Tránsito\".', '2025-06-29 22:25:36'),
(18, 17, 'nicolas', 'dir-atencion-primaria', 'item_added', 'Nuevo ítem \"item\" añadido al área \"Despacho Médico Principal\".', '2025-06-30 00:55:59'),
(19, 17, 'nicolas', 'dir-atencion-primaria', 'item_deleted', 'Ítem \"item\" eliminado del área \"Despacho Médico Principal\".', '2025-06-30 00:56:52'),
(20, 17, 'nicolas', 'dir-atencion-primaria', 'items_imported', '1 ítem(s) importado(s) correctamente.', '2025-06-30 01:09:52'),
(21, 17, 'nicolas', 'dir-atencion-primaria', 'item_deleted', 'Ítem \"item\" eliminado del área \"Despacho Médico Principal\".', '2025-06-30 01:11:25'),
(22, 17, 'nicolas', 'dir-atencion-primaria', 'items_imported', '1 ítem(s) importado(s) correctamente.', '2025-06-30 01:13:34'),
(23, 17, 'nicolas', 'dir-atencion-primaria', 'item_deleted', 'Ítem \"item\" eliminado del área \"Despacho Médico Principal\".', '2025-06-30 01:13:45'),
(24, 17, 'nicolas', 'dir-atencion-primaria', 'item_added', 'Nuevo ítem \"item 2\" añadido al área \"Despacho Médico Principal\".', '2025-06-30 01:14:35'),
(25, 17, 'nicolas', 'dir-atencion-primaria', 'item_deleted', 'Ítem \"item 2\" eliminado del área \"Despacho Médico Principal\".', '2025-06-30 01:15:03'),
(26, 17, 'nicolas', 'dir-atencion-primaria', 'items_imported', '1 ítem(s) importado(s) correctamente.', '2025-06-30 01:15:10'),
(27, 17, 'nicolas', 'dir-atencion-primaria', 'item_edited', 'Ítem \"item 3\" editado en el área \"Despacho Médico Principal\".', '2025-06-30 01:15:24'),
(28, 17, 'nicolas', 'dir-atencion-primaria', 'item_deleted', 'Ítem \"item 3\" eliminado del área \"Despacho Médico Principal\".', '2025-06-30 01:16:15'),
(29, 17, 'nicolas', 'dir-atencion-primaria', 'item_added', 'Nuevo ítem \"un item\" añadido al área \"Despacho Médico Principal\".', '2025-07-06 17:45:35'),
(30, 17, 'nicolas', 'dir-atencion-primaria', 'item_edited', 'Ítem \"un item\" editado en el área \"Despacho Médico Principal\".', '2025-07-06 17:46:29'),
(31, 17, 'nicolas', 'dir-atencion-primaria', 'item_transferred', 'Ítem \"un item (ID: 17)\" traspasado de \"Despacho Médico Principal\" a \"Intendencia Municipal > Secretaría de Gobierno > Dirección de Tránsito\". Motivo: se fue de viaje', '2025-07-06 18:26:03'),
(32, 20, 'admin', NULL, 'item_deleted', 'Ítem \"item2\" eliminado del área \"Todas las Áreas\".', '2025-07-06 23:38:47'),
(33, 20, 'admin', NULL, 'item_deleted', 'Ítem \"un item\" eliminado del área \"Todas las Áreas\".', '2025-07-06 23:38:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` int(11) NOT NULL,
  `node_id` varchar(100) NOT NULL COMMENT 'ID del área/dpto del organigrama',
  `name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `category` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `acquisitionDate` date DEFAULT NULL,
  `status` char(1) NOT NULL DEFAULT 'A' COMMENT 'A=Apto, N=No Apto, R=Recuperable, B=Baja',
  `imagePath` varchar(255) DEFAULT NULL COMMENT 'Ruta a la imagen en el servidor',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `area_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `created_at`, `area_id`) VALUES
(17, 'Nicolas', '$2y$10$.5kiZEyezkV411YMAMRB1OmQeNwTpy.FotRQ8EaawBtx2jYHkncz.', '2025-06-29 21:11:32', 'dir-atencion-primaria'),
(18, 'Seba', '$2y$10$DWQd/OUkhLMLD4FfN1N2a.VDREiHfUpM483M6opK.EDCUNs6iDhhS', '2025-06-29 21:37:00', 'dir-transito'),
(20, 'admin', '$2y$10$.5kiZEyezkV411YMAMRB1OmQeNwTpy.FotRQ8EaawBtx2jYHkncz.', '2025-07-06 23:38:02', NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id_index` (`user_id`),
  ADD KEY `action_type_index` (`action_type`);

--
-- Indices de la tabla `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `node_id_index` (`node_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT de la tabla `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
