-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 17-11-2026 a las 18:30:13
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
-- Base de datos: `coche_seguro`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `checklist_puntos`
--

CREATE TABLE `checklist_puntos` (
  `id` int(11) NOT NULL,
  `modelo_id` int(11) NOT NULL,
  `seccion` varchar(50) NOT NULL,
  `descripcion` text NOT NULL,
  `que_significa_fallo` text NOT NULL,
  `gravedad` enum('Leve','Grave','Critico') DEFAULT 'Grave'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `checklist_puntos`
--

INSERT INTO `checklist_puntos` (`id`, `modelo_id`, `seccion`, `descripcion`, `que_significa_fallo`, `gravedad`) VALUES
(1, 1, 'Bajo el capó', 'Comprobar vaso de expansión de refrigerante en frío', 'Líquido marrón o con restos de aceite indica fallo de enfriador o culata tocada.', 'Critico'),
(2, 1, 'Interior', 'Revisar la tela del techo en la parte trasera', 'Defecto endémico: la espuma se degrada y el techo se descuelga (cuesta ~150€ tapizar).', 'Leve'),
(3, 1, 'Prueba dinámica', 'Acelerar en 4ª marcha desde 1.800 rpm a fondo', 'Vibraciones fuertes en el pedal o habitáculo indican bimasa gastado.', 'Grave'),
(16, 2, 'Bajo el capo', 'Inspeccionar manguito del intercooler en los motores diesel', 'Suelen rajarse con los anos y provocar perdida de potencia y humo negro.', 'Grave'),
(17, 2, 'Cuadro de mandos', 'Dar golpes suaves encima del cuadro de instrumentos', 'Soldaduras frias en el conector del cuadro que causan fallos electricos intermitentes.', 'Grave'),
(18, 2, 'Bajos', 'Comprobar holguras en silentblocks del tren trasero', 'El esquema multibrazo pierde precision si los silentblocks tienen mas de 15 anos.', 'Grave'),
(19, 3, 'Bajo el capo', 'Escuchar ruido de siseo o rozamiento metalico a 1500 rpm en caliente en motores N47', 'Indicio claro de holgura y desgaste en los patines de la cadena de distribucion.', 'Grave'),
(20, 3, 'Prueba dinamica', 'Comprobar dureza y retardo en la direccion asistida', 'Bomba de direccion propensa a perder liquido por las abrazaderas del deposito.', 'Grave'),
(21, 3, 'Interior', 'Revisar desgaste en el tirador de plastico de la puerta del copiloto', 'El recubrimiento gomoso se deshace con el calor y queda pegajoso.', 'Leve'),
(22, 3, 'Exterior / Bajos', 'Buscar óxido en los pasos de rueda traseros, taloneras y marco del techo solar.', 'Mal endémico del modelo por desagües obstruidos. Reparar chapa estructural y encontrar paneles de sustitución dispara el presupuesto.', 'Critico'),
(23, 6, 'Prueba dinámica', 'Girar la dirección a tope en parado y a baja velocidad para comprobar el eje trasero (versiones 4WS).', 'El sistema 4WS es 100% mecánico y muy fiable, pero si tuvo un golpe trasero mal reparado o la cremallera trasera tiene holgura, el coche pisará desalineado.', 'Critico'),
(24, 6, 'Bajo el capó', 'Comprobar que el coche arranque al toque en caliente y revisar estado del relé principal (Main Relay).', 'Famoso fallo de soldaduras frías en el Main Relay de Honda de los años 80/90: el motor gira pero no llega gasolina hasta que enfría el habitáculo.', 'Grave'),
(25, 6, 'Interior', 'Comprobar que los faros escamoteables suban y bajen sincronizados y revisar estado de molduras y pilotos.', 'Los motores de los faros suelen fallar por relés o varillaje agarrotado; además, las piezas estéticas y ópticas originales son escasas y caras en el mercado de segunda mano.', 'Grave'),
(26, 6, 'Bajo el capó', 'Observar la aguja del cuentarrevoluciones en caliente y al encender luces o aire acondicionado (estabilidad del ralentí / válvula IACV).', 'Oscilaciones constantes entre 1.000 y 2.000 rpm (\"ralentí cazando\") o caladas al soltar gas. Suele deberse a suciedad acumulada en la válvula IACV (EACV) o aire en el circuito de refrigeración (FITV). Limpiar la válvula y purgar el circuito lo soluciona en la mayoría de los casos sin tener que sustituirla.', 'Grave');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modelos`
--

CREATE TABLE `modelos` (
  `id` int(11) NOT NULL,
  `marca` varchar(50) NOT NULL,
  `modelo` varchar(50) NOT NULL,
  `generacion` varchar(50) NOT NULL,
  `anio_inicio` int(11) NOT NULL,
  `anio_fin` int(11) NOT NULL,
  `puntuacion_general` decimal(2,1) DEFAULT 4.0,
  `resumen_veredicto` text DEFAULT NULL,
  `imagen_url` varchar(255) DEFAULT 'assets/img/default-car.jpg',
  `uso_ideal` varchar(50) DEFAULT 'Uso mixto',
  `precio_orientativo` int(11) NOT NULL DEFAULT 5000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `modelos`
--

INSERT INTO `modelos` (`id`, `marca`, `modelo`, `generacion`, `anio_inicio`, `anio_fin`, `puntuacion_general`, `resumen_veredicto`, `imagen_url`, `uso_ideal`, `precio_orientativo`) VALUES
(1, 'Volkswagen', 'Golf', 'Mk5 (1K)', 2003, 2008, 3.8, 'Excelente chasis y seguridad. Repuestos por todas partes, pero ojo crítico a los primeros 2.0 TDI y a las cajas DSG sin mantenimiento.', 'assets/img/golf-mk5.jpg', 'Uso mixto', 5000),
(2, 'Ford', 'Focus', 'Mk2', 2004, 2011, 4.0, 'Excelente chasis multibrazo. Los gasolina 1.6 son casi indestructibles y los 1.6/2.0 TDCi son ruteros y economicos si se cuida el mantenimiento.', 'assets/img/focus-mk2.jpg', 'Viajes largos', 5000),
(3, 'BMW', 'Serie 3', 'E90', 2005, 2012, 3.2, 'Dinamica de conduccion impecable y acabados premium, pero requiere ser muy selectivo con la motorizacion por el riesgo de distribucion.', 'assets/img/bmw-e90.jpg', 'Viajes largos', 5000),
(6, 'Honda', 'Prelude', 'BA4-5', 1987, 1991, 4.0, 'Clásico japonés con un paso por curva histórico gracias a su suspensión delantera de doble horquilla y el sistema mecánico de 4 ruedas directrices (4WS). Mecánica muy agradecida si se respeta el mantenimiento, pero la escasez de recambios de carrocería e interior exige buscar una unidad completa y libre de óxido.', 'assets/img/honda-prelude-ba4.jpg', 'Deportivo / Disfrute', 5000);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `motores`
--

CREATE TABLE `motores` (
  `id` int(11) NOT NULL,
  `modelo_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `combustible` enum('Diésel','Gasolina','Híbrido') NOT NULL,
  `semaforo` enum('Verde','Amarillo','Rojo') NOT NULL,
  `opinion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `motores`
--

INSERT INTO `motores` (`id`, `modelo_id`, `nombre`, `combustible`, `semaforo`, `opinion`) VALUES
(1, 1, '1.9 TDI 105cv', 'Diésel', 'Verde', 'Roca indestructible si se le cambia el aceite a tiempo. Consumos de 5L/100km.'),
(2, 1, '2.0 TDI 140cv (Culata BKP/BKD)', 'Diésel', 'Rojo', 'Famoso por fisuras en culata y consumo de refrigerante en las primeras series.'),
(3, 1, '1.6 MPI 102cv', 'Gasolina', 'Verde', 'Motor atmosférico simple de 8 válvulas, cero problemas mecánicos aunque algo perezoso.'),
(19, 2, '1.6 Duratec 100cv', 'Gasolina', 'Verde', 'Motor atmosferico veterano, sin turbo ni complicaciones. Algo tragon pero muy fiable.'),
(20, 2, '1.6 TDCi 109cv', 'Diésel', 'Amarillo', 'Motor PSA/Ford. Muy economico, pero sensible al turbo si no se respeta el cambio de aceite y se limpia el tamiz del engrase.'),
(21, 2, '2.0 TDCi 136cv', 'Diésel', 'Verde', 'Bloque PSA robusto con cambio manual de 6 marchas. Buen empuje y gran durabilidad.'),
(22, 3, '320d 163cv - Motor M47', 'Diésel', 'Verde', 'Distribucion delantera fiable. Puntos debiles: palomillas de admision y turbo en unidades tempranas.'),
(23, 3, '320d 163cv - Motor M47', 'Diésel', 'Verde', 'Distribucion delantera fiable. Puntos debiles: palomillas de admision y turbo en unidades tempranas.'),
(24, 3, '320d 177cv / 184cv - Motor N47', 'Diésel', 'Rojo', 'Famoso por rotura de cadena de distribucion trasera. Si no esta cambiada con factura demostrable, riesgo de averia de mas de 2000 euros.'),
(25, 3, '325i / 330i - Motor N52 6 cilindros', 'Gasolina', 'Verde', 'Sonido y suavidad excelentes. Revisar bomba de agua electrica y junta de tapa de balancines.'),
(26, 6, 'B20A7', 'Gasolina', 'Verde', 'El motor estrella de esta generación (150cv). Muy elástico y fiable. Vigilar fugas por la junta de tapa de balancines, el retén del distribuidor,CYL sensor, etc. Respetar estrictamente el cambio de kit de distribución.'),
(27, 6, 'B20A3', 'Gasolina', 'Verde', 'Bloque mecánicamente indestructible, pero el sistema de doble carburador requiere alguien que sepa sincronizarlos y ajustar los tubos de vacío cuando fallan los ralentís en frío.'),
(28, 6, 'B20A5', 'Gasolina', 'Verde', 'Variante de 135-140cv (muy común en mercados internacionales y unidades importadas, catalizada) con la misma base robusta de doble árbol de levas que el B20A7. Muy fiable y suave si lleva el mantenimiento al día, aunque algo menos rabioso en altas por la gestión electrónica y el catalizador. Vigilar fugas de aceite por el retén del distribuidor, junta de tapa de balancines y desgaste en los soportes de motor.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportes`
--

CREATE TABLE `reportes` (
  `id` int(11) NOT NULL,
  `modelo_id` int(11) NOT NULL,
  `tipo` enum('Nuevo fallo / punto','Dato erróneo','Otro') DEFAULT 'Nuevo fallo / punto',
  `mensaje` text NOT NULL,
  `contacto` varchar(100) DEFAULT NULL,
  `estado` enum('Pendiente','Revisado') DEFAULT 'Pendiente',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `reportes`
--

INSERT INTO `reportes` (`id`, `modelo_id`, `tipo`, `mensaje`, `contacto`, `estado`, `fecha_creacion`) VALUES
(1, 6, 'Otro', 'no había una versión Type R?', 'Enrique', 'Pendiente', '2026-11-16 23:51:21');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `checklist_puntos`
--
ALTER TABLE `checklist_puntos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `modelo_id` (`modelo_id`);

--
-- Indices de la tabla `modelos`
--
ALTER TABLE `modelos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `motores`
--
ALTER TABLE `motores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `modelo_id` (`modelo_id`);

--
-- Indices de la tabla `reportes`
--
ALTER TABLE `reportes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `modelo_id` (`modelo_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `checklist_puntos`
--
ALTER TABLE `checklist_puntos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `modelos`
--
ALTER TABLE `modelos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `motores`
--
ALTER TABLE `motores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de la tabla `reportes`
--
ALTER TABLE `reportes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `checklist_puntos`
--
ALTER TABLE `checklist_puntos`
  ADD CONSTRAINT `checklist_puntos_ibfk_1` FOREIGN KEY (`modelo_id`) REFERENCES `modelos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `motores`
--
ALTER TABLE `motores`
  ADD CONSTRAINT `motores_ibfk_1` FOREIGN KEY (`modelo_id`) REFERENCES `modelos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `reportes`
--
ALTER TABLE `reportes`
  ADD CONSTRAINT `reportes_ibfk_1` FOREIGN KEY (`modelo_id`) REFERENCES `modelos` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
