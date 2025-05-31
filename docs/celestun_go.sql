-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 31-05-2025 a las 16:46:30
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `celestun_go`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carrito`
--

CREATE TABLE `carrito` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `producto_id` int(11) DEFAULT NULL,
  `cantidad` int(11) NOT NULL,
  `fecha_agregado` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `carrito`
--

INSERT INTO `carrito` (`id`, `usuario_id`, `producto_id`, `cantidad`, `fecha_agregado`) VALUES
(1, 1, 1, 1, '2025-05-31 01:24:06'),
(2, 1, 4, 2, '2025-05-31 01:24:06'),
(3, 2, 5, 1, '2025-05-31 01:24:06'),
(4, 2, 8, 1, '2025-05-31 01:24:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `icono` varchar(10) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `icono`, `activo`, `fecha_creacion`) VALUES
(1, 'Pizza', '🍕', 1, '2025-05-31 00:59:52'),
(2, 'Tacos', '🌮', 1, '2025-05-31 00:59:52'),
(3, 'Burgers', '🍔', 1, '2025-05-31 00:59:52'),
(4, 'Saludable', '🥗', 1, '2025-05-31 00:59:52'),
(5, 'Sushi', '🍣', 1, '2025-05-31 00:59:52'),
(6, 'Mariscos', '🦐', 1, '2025-05-31 00:59:52'),
(7, 'Postres', '🧁', 1, '2025-05-31 00:59:52'),
(8, 'Bebidas', '☕', 1, '2025-05-31 00:59:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_sistema`
--

CREATE TABLE `configuracion_sistema` (
  `id` int(11) NOT NULL,
  `clave` varchar(50) NOT NULL,
  `valor` text NOT NULL,
  `tipo` enum('texto','numero','booleano','json') DEFAULT 'texto',
  `descripcion` varchar(255) DEFAULT NULL,
  `categoria` varchar(50) DEFAULT 'general',
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `actualizado_por` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `configuracion_sistema`
--

INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `descripcion`, `categoria`, `fecha_actualizacion`, `actualizado_por`) VALUES
(1, 'app_nombre', 'Celestún GO', 'texto', 'Nombre de la aplicación', 'general', '2025-05-31 12:35:15', NULL),
(2, 'app_version', '1.0.0', 'texto', 'Versión actual de la app', 'general', '2025-05-31 12:35:15', NULL),
(3, 'delivery_radio_km', '10', 'numero', 'Radio de entrega en kilómetros', 'delivery', '2025-05-31 12:35:15', NULL),
(4, 'comision_plataforma', '10', 'numero', 'Comisión de la plataforma en porcentaje', 'finanzas', '2025-05-31 12:35:15', NULL),
(5, 'tiempo_preparacion_default', '30', 'numero', 'Tiempo de preparación por defecto en minutos', 'delivery', '2025-05-31 12:35:15', NULL),
(6, 'costo_envio_default', '25', 'numero', 'Costo de envío por defecto', 'delivery', '2025-05-31 12:35:15', NULL),
(7, 'monto_envio_gratis', '200', 'numero', 'Monto mínimo para envío gratis', 'delivery', '2025-05-31 12:35:15', NULL),
(8, 'horario_servicio_inicio', '08:00', 'texto', 'Hora de inicio del servicio', 'horarios', '2025-05-31 12:35:15', NULL),
(9, 'horario_servicio_fin', '22:00', 'texto', 'Hora de fin del servicio', 'horarios', '2025-05-31 12:35:15', NULL),
(10, 'acepta_nuevos_restaurantes', 'true', 'booleano', 'Si se aceptan nuevas solicitudes de restaurantes', 'registro', '2025-05-31 12:35:15', NULL),
(11, 'mensaje_mantenimiento', '', 'texto', 'Mensaje a mostrar durante mantenimiento', 'sistema', '2025-05-31 12:35:15', NULL),
(12, 'modo_mantenimiento', 'false', 'booleano', 'Activar modo mantenimiento', 'sistema', '2025-05-31 12:35:15', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `logs_actividad`
--

CREATE TABLE `logs_actividad` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `accion` varchar(100) NOT NULL,
  `tabla_afectada` varchar(50) DEFAULT NULL,
  `registro_id` int(11) DEFAULT NULL,
  `datos_anteriores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_anteriores`)),
  `datos_nuevos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_nuevos`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `fecha_accion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('pedido','sistema','promocion','restaurante') NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `mensaje` text NOT NULL,
  `leida` tinyint(1) DEFAULT 0,
  `datos_extra` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_extra`)),
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_leida` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `restaurante_id` int(11) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `costo_envio` decimal(10,2) DEFAULT 0.00,
  `direccion_entrega` varchar(200) NOT NULL,
  `telefono_contacto` varchar(15) NOT NULL,
  `estado` enum('pendiente','confirmado','preparando','en_camino','entregado','cancelado') DEFAULT 'pendiente',
  `metodo_pago` enum('efectivo','tarjeta','transferencia') DEFAULT 'efectivo',
  `notas` text DEFAULT NULL,
  `fecha_pedido` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_entrega` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id`, `usuario_id`, `restaurante_id`, `total`, `costo_envio`, `direccion_entrega`, `telefono_contacto`, `estado`, `metodo_pago`, `notas`, `fecha_pedido`, `fecha_entrega`) VALUES
(1, 1, 1, 205.00, 0.00, 'Calle 12 #34, Centro, Celestún', '9991234567', 'entregado', 'efectivo', 'Sin cebolla en la pizza', '2023-05-30 20:30:00', '2023-05-30 21:15:00'),
(2, 2, 2, 175.00, 25.00, 'Calle 10 #56, Centro, Celestún', '9991234568', 'en_camino', 'tarjeta', NULL, '2023-05-31 18:15:00', NULL),
(3, 3, 3, 200.00, 20.00, 'Malecón #12, Frente al mar, Celestún', '9991234569', 'pendiente', 'efectivo', NULL, '2023-05-31 19:45:00', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido_detalles`
--

CREATE TABLE `pedido_detalles` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) DEFAULT NULL,
  `producto_id` int(11) DEFAULT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedido_detalles`
--

INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(1, 1, 1, 1, 180.00, 180.00),
(2, 1, 4, 1, 25.00, 25.00),
(3, 2, 5, 1, 120.00, 120.00),
(4, 2, 8, 1, 30.00, 30.00),
(5, 3, 9, 2, 85.00, 170.00),
(6, 3, 12, 1, 35.00, 35.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `imagen_url` varchar(255) DEFAULT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `restaurante_id` int(11) DEFAULT NULL,
  `disponible` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `nombre`, `descripcion`, `precio`, `imagen_url`, `categoria_id`, `restaurante_id`, `disponible`, `fecha_creacion`) VALUES
(1, 'Pizza Hawaiana Grande', 'Jamón, piña, queso mozzarella, salsa especial', 180.00, 'pizza-hawaiana.jpg', 1, 1, 1, '2025-05-31 00:59:52'),
(2, 'Pizza Pepperoni Mediana', 'Pepperoni, queso mozzarella, orégano', 150.00, 'pizza-pepperoni.jpg', 1, 1, 1, '2025-05-31 00:59:52'),
(3, 'Pizza Vegetariana Grande', 'Pimientos, champiñones, cebolla, aceitunas', 170.00, 'pizza-vegetariana.jpg', 1, 1, 1, '2025-05-31 00:59:52'),
(4, 'Refresco 600ml', 'Coca Cola, Sprite, Fanta', 25.00, NULL, 8, 1, 1, '2025-05-31 00:59:52'),
(5, 'Ceviche de Pescado', 'Pescado fresco, limón, cebolla, cilantro, aguacate', 120.00, 'ceviche-pescado.jpg', 6, 2, 1, '2025-05-31 00:59:52'),
(6, 'Ceviche Mixto', 'Pescado, camarón, pulpo, limón, verduras frescas', 150.00, 'ceviche-mixto.jpg', 6, 2, 1, '2025-05-31 00:59:52'),
(7, 'Filete de Pescado', 'Filete empanizado con arroz y ensalada', 180.00, NULL, 6, 2, 1, '2025-05-31 00:59:52'),
(8, 'Agua de Horchata', 'Bebida tradicional yucateca', 30.00, NULL, 8, 2, 1, '2025-05-31 00:59:52'),
(9, 'Tacos de Cochinita (3 pzas)', 'Cochinita pibil, cebolla morada, salsa', 85.00, 'tacos-cochinita.jpg', 2, 3, 1, '2025-05-31 00:59:52'),
(10, 'Tacos de Pastor (3 pzas)', 'Carne al pastor, piña, cebolla, cilantro', 90.00, 'tacos-pastor.jpg', 2, 3, 1, '2025-05-31 00:59:52'),
(11, 'Torta de Cochinita', 'Pan francés, cochinita, frijoles, aguacate', 65.00, NULL, 2, 3, 1, '2025-05-31 00:59:52'),
(12, 'Horchata Grande', 'Bebida de arroz con canela', 35.00, NULL, 8, 3, 1, '2025-05-31 00:59:52'),
(13, 'Ensalada César', 'Lechuga, pollo, crutones, aderezo césar', 110.00, NULL, 4, 4, 1, '2025-05-31 00:59:52'),
(14, 'Bowl de Frutas', 'Frutas frescas de temporada con granola', 85.00, NULL, 4, 4, 1, '2025-05-31 00:59:52'),
(15, 'Café Americano', 'Café de especialidad de la región', 40.00, NULL, 8, 4, 1, '2025-05-31 00:59:52'),
(16, 'Smoothie Verde', 'Espinaca, piña, mango, agua de coco', 75.00, NULL, 8, 4, 1, '2025-05-31 00:59:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_disponibilidad`
--

CREATE TABLE `producto_disponibilidad` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `disponible` tinyint(1) DEFAULT 1,
  `cantidad_estimada` int(11) DEFAULT NULL,
  `precio_actual` decimal(10,2) NOT NULL,
  `notas` varchar(255) DEFAULT NULL,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `actualizado_por` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `producto_disponibilidad`
--

INSERT INTO `producto_disponibilidad` (`id`, `producto_id`, `disponible`, `cantidad_estimada`, `precio_actual`, `notas`, `fecha_actualizacion`, `actualizado_por`) VALUES
(1, 1, 1, NULL, 180.00, 'Disponibilidad inicial', '2025-05-31 12:35:15', NULL),
(2, 2, 1, NULL, 150.00, 'Disponibilidad inicial', '2025-05-31 12:35:15', NULL),
(3, 3, 1, NULL, 170.00, 'Disponibilidad inicial', '2025-05-31 12:35:15', NULL),
(4, 4, 1, NULL, 25.00, 'Disponibilidad inicial', '2025-05-31 12:35:15', NULL),
(5, 5, 1, NULL, 120.00, 'Disponibilidad inicial', '2025-05-31 12:35:15', NULL),
(6, 6, 1, NULL, 150.00, 'Disponibilidad inicial', '2025-05-31 12:35:15', NULL),
(7, 7, 1, NULL, 180.00, 'Disponibilidad inicial', '2025-05-31 12:35:15', NULL),
(8, 8, 1, NULL, 30.00, 'Disponibilidad inicial', '2025-05-31 12:35:15', NULL),
(9, 9, 1, NULL, 85.00, 'Disponibilidad inicial', '2025-05-31 12:35:15', NULL),
(10, 10, 1, NULL, 90.00, 'Disponibilidad inicial', '2025-05-31 12:35:15', NULL),
(11, 11, 1, NULL, 65.00, 'Disponibilidad inicial', '2025-05-31 12:35:15', NULL),
(12, 12, 1, NULL, 35.00, 'Disponibilidad inicial', '2025-05-31 12:35:15', NULL),
(13, 13, 1, NULL, 110.00, 'Disponibilidad inicial', '2025-05-31 12:35:15', NULL),
(14, 14, 1, NULL, 85.00, 'Disponibilidad inicial', '2025-05-31 12:35:15', NULL),
(15, 15, 1, NULL, 40.00, 'Disponibilidad inicial', '2025-05-31 12:35:15', NULL),
(16, 16, 1, NULL, 75.00, 'Disponibilidad inicial', '2025-05-31 12:35:15', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `promociones`
--

CREATE TABLE `promociones` (
  `id` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo_descuento` enum('porcentaje','monto_fijo','envio_gratis') NOT NULL,
  `valor_descuento` decimal(10,2) NOT NULL,
  `monto_minimo` decimal(10,2) DEFAULT 0.00,
  `restaurante_id` int(11) DEFAULT NULL,
  `usos_maximos` int(11) DEFAULT NULL,
  `usos_actuales` int(11) DEFAULT 0,
  `fecha_inicio` datetime NOT NULL,
  `fecha_fin` datetime NOT NULL,
  `activa` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `creado_por` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `promociones_uso`
--

CREATE TABLE `promociones_uso` (
  `id` int(11) NOT NULL,
  `promocion_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `descuento_aplicado` decimal(10,2) NOT NULL,
  `fecha_uso` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `restaurantes`
--

CREATE TABLE `restaurantes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `imagen_url` varchar(255) DEFAULT NULL,
  `calificacion` decimal(2,1) DEFAULT 0.0,
  `tiempo_entrega_min` int(11) DEFAULT 30,
  `tiempo_entrega_max` int(11) DEFAULT 45,
  `costo_envio` decimal(10,2) DEFAULT 0.00,
  `envio_gratis_desde` decimal(10,2) DEFAULT 200.00,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `restaurantes`
--

INSERT INTO `restaurantes` (`id`, `nombre`, `descripcion`, `telefono`, `direccion`, `imagen_url`, `calificacion`, `tiempo_entrega_min`, `tiempo_entrega_max`, `costo_envio`, `envio_gratis_desde`, `activo`, `fecha_creacion`) VALUES
(1, 'Pizzería Flamingo', 'Las mejores pizzas de Celestún con ingredientes frescos', '9991234567', 'Calle 12 #45, Centro', 'pizza-flamingo.jpg', 4.8, 25, 35, 0.00, 200.00, 1, '2025-05-31 00:59:52'),
(2, 'Mariscos El Pelícano', 'Mariscos frescos del día, especialidad en ceviches', '9991234568', 'Malecón s/n, Frente al mar', 'mariscos-pelicano.jpg', 4.9, 20, 30, 25.00, 250.00, 1, '2025-05-31 00:59:52'),
(3, 'Taquería La Gaviota', 'Tacos tradicionales yucatecos, abierto hasta tarde', '9991234569', 'Calle 10 #23, Centro', 'tacos-gaviota.jpg', 4.7, 15, 25, 20.00, 150.00, 1, '2025-05-31 00:59:52'),
(4, 'Café Manglar', 'Café de especialidad y desayunos saludables', '9991234570', 'Calle 14 #67, Centro', 'cafe-manglar.jpg', 4.6, 10, 20, 15.00, 180.00, 1, '2025-05-31 00:59:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `restaurante_horarios`
--

CREATE TABLE `restaurante_horarios` (
  `id` int(11) NOT NULL,
  `restaurante_id` int(11) NOT NULL,
  `dia_semana` enum('lunes','martes','miercoles','jueves','viernes','sabado','domingo') NOT NULL,
  `hora_apertura` time NOT NULL,
  `hora_cierre` time NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `descanso_inicio` time DEFAULT NULL,
  `descanso_fin` time DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `restaurante_horarios`
--

INSERT INTO `restaurante_horarios` (`id`, `restaurante_id`, `dia_semana`, `hora_apertura`, `hora_cierre`, `activo`, `descanso_inicio`, `descanso_fin`, `fecha_creacion`) VALUES
(1, 1, 'lunes', '12:00:00', '23:00:00', 1, NULL, NULL, '2025-05-31 12:35:15'),
(2, 1, 'martes', '12:00:00', '23:00:00', 1, NULL, NULL, '2025-05-31 12:35:15'),
(3, 1, 'miercoles', '12:00:00', '23:00:00', 1, NULL, NULL, '2025-05-31 12:35:15'),
(4, 1, 'jueves', '12:00:00', '23:00:00', 1, NULL, NULL, '2025-05-31 12:35:15'),
(5, 1, 'viernes', '12:00:00', '24:00:00', 1, NULL, NULL, '2025-05-31 12:35:15'),
(6, 1, 'sabado', '12:00:00', '24:00:00', 1, NULL, NULL, '2025-05-31 12:35:15'),
(7, 1, 'domingo', '14:00:00', '23:00:00', 1, NULL, NULL, '2025-05-31 12:35:15'),
(8, 2, 'martes', '11:00:00', '22:00:00', 1, NULL, NULL, '2025-05-31 12:35:15'),
(9, 2, 'miercoles', '11:00:00', '22:00:00', 1, NULL, NULL, '2025-05-31 12:35:15'),
(10, 2, 'jueves', '11:00:00', '22:00:00', 1, NULL, NULL, '2025-05-31 12:35:15'),
(11, 2, 'viernes', '11:00:00', '23:00:00', 1, NULL, NULL, '2025-05-31 12:35:15'),
(12, 2, 'sabado', '10:00:00', '23:00:00', 1, NULL, NULL, '2025-05-31 12:35:15'),
(13, 2, 'domingo', '10:00:00', '22:00:00', 1, NULL, NULL, '2025-05-31 12:35:15'),
(14, 3, 'lunes', '18:00:00', '02:00:00', 1, NULL, NULL, '2025-05-31 12:35:15'),
(15, 3, 'martes', '18:00:00', '02:00:00', 1, NULL, NULL, '2025-05-31 12:35:15'),
(16, 3, 'miercoles', '18:00:00', '02:00:00', 1, NULL, NULL, '2025-05-31 12:35:15'),
(17, 3, 'jueves', '18:00:00', '03:00:00', 1, NULL, NULL, '2025-05-31 12:35:15'),
(18, 3, 'viernes', '18:00:00', '03:00:00', 1, NULL, NULL, '2025-05-31 12:35:15'),
(19, 3, 'sabado', '18:00:00', '03:00:00', 1, NULL, NULL, '2025-05-31 12:35:15'),
(20, 3, 'domingo', '18:00:00', '01:00:00', 1, NULL, NULL, '2025-05-31 12:35:15'),
(21, 4, 'lunes', '07:00:00', '17:00:00', 1, NULL, NULL, '2025-05-31 12:35:15'),
(22, 4, 'martes', '07:00:00', '17:00:00', 1, NULL, NULL, '2025-05-31 12:35:15'),
(23, 4, 'miercoles', '07:00:00', '17:00:00', 1, NULL, NULL, '2025-05-31 12:35:15'),
(24, 4, 'jueves', '07:00:00', '17:00:00', 1, NULL, NULL, '2025-05-31 12:35:15'),
(25, 4, 'viernes', '07:00:00', '18:00:00', 1, NULL, NULL, '2025-05-31 12:35:15'),
(26, 4, 'sabado', '08:00:00', '18:00:00', 1, NULL, NULL, '2025-05-31 12:35:15'),
(27, 4, 'domingo', '08:00:00', '16:00:00', 1, NULL, NULL, '2025-05-31 12:35:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `restaurante_solicitudes`
--

CREATE TABLE `restaurante_solicitudes` (
  `id` int(11) NOT NULL,
  `nombre_comercial` varchar(100) NOT NULL,
  `nombre_propietario` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefono` varchar(15) NOT NULL,
  `direccion` varchar(200) NOT NULL,
  `tipo_cocina` varchar(50) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `documentos_legales` text DEFAULT NULL,
  `rfc` varchar(20) DEFAULT NULL,
  `licencia_funcionamiento` varchar(50) DEFAULT NULL,
  `estado` enum('pendiente','en_revision','aprobado','rechazado') DEFAULT 'pendiente',
  `motivo_rechazo` text DEFAULT NULL,
  `fecha_solicitud` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_revision` timestamp NULL DEFAULT NULL,
  `revisado_por` int(11) DEFAULT NULL,
  `notas_admin` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `restaurante_solicitudes`
--

INSERT INTO `restaurante_solicitudes` (`id`, `nombre_comercial`, `nombre_propietario`, `email`, `telefono`, `direccion`, `tipo_cocina`, `descripcion`, `documentos_legales`, `rfc`, `licencia_funcionamiento`, `estado`, `motivo_rechazo`, `fecha_solicitud`, `fecha_revision`, `revisado_por`, `notas_admin`) VALUES
(1, 'Antojitos Doña Carmen', 'Carmen Pérez', 'carmen@antojitos.com', '9991234571', 'Calle 8 #89, Centro', 'Tradicional Yucateca', 'Especialistas en cochinita pibil y sopa de lima casera', NULL, 'RFC123456789', 'LIC-2023-001', 'pendiente', NULL, '2025-05-31 12:35:15', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `direccion_principal` varchar(200) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1,
  `rol` enum('cliente','admin','restaurante','repartidor') DEFAULT 'cliente',
  `restaurante_id` int(11) DEFAULT NULL,
  `ultimo_acceso` timestamp NULL DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `verificado` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `telefono`, `password_hash`, `direccion_principal`, `fecha_registro`, `activo`, `rol`, `restaurante_id`, `ultimo_acceso`, `avatar_url`, `verificado`) VALUES
(1, 'Juan Pérez', 'juan@test.com', '9991234567', NULL, 'Calle 12 #34, Centro, Celestún', '2025-05-31 01:24:06', 1, 'admin', NULL, NULL, NULL, 0),
(2, 'María González', 'maria@test.com', '9991234568', NULL, 'Calle 10 #56, Centro, Celestún', '2025-05-31 01:24:06', 1, 'cliente', NULL, NULL, NULL, 0),
(3, 'Carlos López', 'carlos@test.com', '9991234569', NULL, 'Malecón #12, Frente al mar, Celestún', '2025-05-31 01:24:06', 1, 'cliente', NULL, NULL, NULL, 0);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `carrito`
--
ALTER TABLE `carrito`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `configuracion_sistema`
--
ALTER TABLE `configuracion_sistema`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`),
  ADD KEY `actualizado_por` (`actualizado_por`);

--
-- Indices de la tabla `logs_actividad`
--
ALTER TABLE `logs_actividad`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_logs_fecha` (`fecha_accion`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notificaciones_usuario_leida` (`usuario_id`,`leida`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `restaurante_id` (`restaurante_id`);

--
-- Indices de la tabla `pedido_detalles`
--
ALTER TABLE `pedido_detalles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categoria_id` (`categoria_id`),
  ADD KEY `restaurante_id` (`restaurante_id`);

--
-- Indices de la tabla `producto_disponibilidad`
--
ALTER TABLE `producto_disponibilidad`
  ADD PRIMARY KEY (`id`),
  ADD KEY `actualizado_por` (`actualizado_por`),
  ADD KEY `idx_producto_disponibilidad_producto` (`producto_id`);

--
-- Indices de la tabla `promociones`
--
ALTER TABLE `promociones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `restaurante_id` (`restaurante_id`),
  ADD KEY `creado_por` (`creado_por`),
  ADD KEY `idx_promociones_codigo` (`codigo`),
  ADD KEY `idx_promociones_fecha` (`fecha_inicio`,`fecha_fin`,`activa`);

--
-- Indices de la tabla `promociones_uso`
--
ALTER TABLE `promociones_uso`
  ADD PRIMARY KEY (`id`),
  ADD KEY `promocion_id` (`promocion_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `pedido_id` (`pedido_id`);

--
-- Indices de la tabla `restaurantes`
--
ALTER TABLE `restaurantes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `restaurante_horarios`
--
ALTER TABLE `restaurante_horarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_restaurante_dia` (`restaurante_id`,`dia_semana`),
  ADD KEY `idx_horarios_restaurante_dia` (`restaurante_id`,`dia_semana`);

--
-- Indices de la tabla `restaurante_solicitudes`
--
ALTER TABLE `restaurante_solicitudes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `revisado_por` (`revisado_por`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `restaurante_id` (`restaurante_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `carrito`
--
ALTER TABLE `carrito`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `configuracion_sistema`
--
ALTER TABLE `configuracion_sistema`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `logs_actividad`
--
ALTER TABLE `logs_actividad`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `pedido_detalles`
--
ALTER TABLE `pedido_detalles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `producto_disponibilidad`
--
ALTER TABLE `producto_disponibilidad`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `promociones`
--
ALTER TABLE `promociones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `promociones_uso`
--
ALTER TABLE `promociones_uso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `restaurantes`
--
ALTER TABLE `restaurantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `restaurante_horarios`
--
ALTER TABLE `restaurante_horarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de la tabla `restaurante_solicitudes`
--
ALTER TABLE `restaurante_solicitudes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `carrito`
--
ALTER TABLE `carrito`
  ADD CONSTRAINT `carrito_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `carrito_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `configuracion_sistema`
--
ALTER TABLE `configuracion_sistema`
  ADD CONSTRAINT `configuracion_sistema_ibfk_1` FOREIGN KEY (`actualizado_por`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `logs_actividad`
--
ALTER TABLE `logs_actividad`
  ADD CONSTRAINT `logs_actividad_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `pedidos_ibfk_2` FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes` (`id`);

--
-- Filtros para la tabla `pedido_detalles`
--
ALTER TABLE `pedido_detalles`
  ADD CONSTRAINT `pedido_detalles_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`),
  ADD CONSTRAINT `pedido_detalles_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`),
  ADD CONSTRAINT `productos_ibfk_2` FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes` (`id`);

--
-- Filtros para la tabla `producto_disponibilidad`
--
ALTER TABLE `producto_disponibilidad`
  ADD CONSTRAINT `producto_disponibilidad_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `producto_disponibilidad_ibfk_2` FOREIGN KEY (`actualizado_por`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `promociones`
--
ALTER TABLE `promociones`
  ADD CONSTRAINT `promociones_ibfk_1` FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes` (`id`),
  ADD CONSTRAINT `promociones_ibfk_2` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `promociones_uso`
--
ALTER TABLE `promociones_uso`
  ADD CONSTRAINT `promociones_uso_ibfk_1` FOREIGN KEY (`promocion_id`) REFERENCES `promociones` (`id`),
  ADD CONSTRAINT `promociones_uso_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `promociones_uso_ibfk_3` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`);

--
-- Filtros para la tabla `restaurante_horarios`
--
ALTER TABLE `restaurante_horarios`
  ADD CONSTRAINT `restaurante_horarios_ibfk_1` FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `restaurante_solicitudes`
--
ALTER TABLE `restaurante_solicitudes`
  ADD CONSTRAINT `restaurante_solicitudes_ibfk_1` FOREIGN KEY (`revisado_por`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
