/*
 Navicat Premium Data Transfer

 Source Server         : laragon2025
 Source Server Type    : MySQL
 Source Server Version : 80030 (8.0.30)
 Source Host           : localhost:3306
 Source Schema         : planilla_prod

 Target Server Type    : MySQL
 Target Server Version : 80030 (8.0.30)
 File Encoding         : 65001

 Date: 20/11/2025 18:22:16
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for acumulados_por_empleado
-- ----------------------------
DROP TABLE IF EXISTS `acumulados_por_empleado`;
CREATE TABLE `acumulados_por_empleado`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL COMMENT 'ID del empleado',
  `concepto_id` int NOT NULL COMMENT 'ID del concepto aplicado',
  `planilla_id` int NOT NULL COMMENT 'ID de la planilla que generó el acumulado',
  `monto` decimal(10, 2) NOT NULL COMMENT 'Monto del concepto en esta planilla',
  `mes` int NOT NULL COMMENT 'Mes de la planilla (1-12)',
  `ano` int NOT NULL COMMENT 'Año de la planilla',
  `frecuencia` int NOT NULL COMMENT 'ID de la frecuencia desde tabla frecuencias',
  `tipo_concepto` enum('ASIGNACION','DEDUCCION','PATRONAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Tipo del concepto',
  `tipo_acumulado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'Tipo específico de acumulado (XIII_MES, VACACIONES, etc.)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_employee_id`(`employee_id` ASC) USING BTREE,
  INDEX `idx_concepto_id`(`concepto_id` ASC) USING BTREE,
  INDEX `idx_planilla_id`(`planilla_id` ASC) USING BTREE,
  INDEX `idx_mes_ano`(`mes` ASC, `ano` ASC) USING BTREE,
  INDEX `idx_employee_concepto_ano`(`employee_id` ASC, `concepto_id` ASC, `ano` ASC) USING BTREE,
  INDEX `idx_emp_concepto_periodo`(`employee_id` ASC, `concepto_id` ASC, `ano` ASC, `mes` ASC) USING BTREE,
  INDEX `idx_planilla_tipo_concepto`(`planilla_id` ASC, `tipo_concepto` ASC) USING BTREE,
  INDEX `idx_tipo_acumulado`(`tipo_acumulado` ASC) USING BTREE,
  INDEX `idx_frecuencia_id`(`frecuencia` ASC) USING BTREE,
  CONSTRAINT `fk_acumulados_frecuencia` FOREIGN KEY (`frecuencia`) REFERENCES `frecuencias` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 2401 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'Acumulados detallados por empleado/concepto/planilla para auditoría' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for acumulados_por_planilla
-- ----------------------------
DROP TABLE IF EXISTS `acumulados_por_planilla`;
CREATE TABLE `acumulados_por_planilla`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL COMMENT 'ID del empleado',
  `planilla_id` int NOT NULL COMMENT 'ID de la planilla',
  `mes` int NOT NULL COMMENT 'Mes de la planilla (1-12)',
  `ano` int NOT NULL COMMENT 'Año de la planilla',
  `frecuencia_id` int NOT NULL,
  `sueldos` decimal(10, 2) NULL DEFAULT 0.00 COMMENT 'Sueldos base (conceptos 1,2,3)',
  `gastos_representacion` decimal(10, 2) NULL DEFAULT 0.00 COMMENT 'Gastos de representación',
  `otras_asignaciones` decimal(10, 2) NULL DEFAULT 0.00 COMMENT 'Otras asignaciones',
  `total_asignaciones` decimal(10, 2) NULL DEFAULT 0.00 COMMENT 'Total de ingresos',
  `seguro_social` decimal(10, 2) NULL DEFAULT 0.00 COMMENT 'Descuento Seguro Social',
  `seguro_educativo` decimal(10, 2) NULL DEFAULT 0.00 COMMENT 'Descuento Seguro Educativo',
  `impuesto_renta` decimal(10, 2) NULL DEFAULT 0.00 COMMENT 'Retención ISR',
  `desc_gastos_ss` decimal(10, 2) NULL DEFAULT 0.00 COMMENT 'Desc. SS sobre gastos rep.',
  `desc_gastos_se` decimal(10, 2) NULL DEFAULT 0.00 COMMENT 'Desc. SE sobre gastos rep.',
  `desc_gastos_isr` decimal(10, 2) NULL DEFAULT 0.00 COMMENT 'Desc. ISR sobre gastos rep.',
  `otras_deducciones` decimal(10, 2) NULL DEFAULT 0.00 COMMENT 'Otras deducciones',
  `total_deducciones` decimal(10, 2) NULL DEFAULT 0.00 COMMENT 'Total de deducciones',
  `total_neto` decimal(10, 2) NULL DEFAULT 0.00 COMMENT 'Neto a pagar',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `unique_employee_planilla`(`employee_id` ASC, `planilla_id` ASC) USING BTREE,
  INDEX `idx_planilla_id`(`planilla_id` ASC) USING BTREE,
  INDEX `idx_mes_ano`(`mes` ASC, `ano` ASC) USING BTREE,
  INDEX `idx_employee_ano`(`employee_id` ASC, `ano` ASC) USING BTREE,
  INDEX `idx_consolidado_periodo`(`ano` ASC, `mes` ASC) USING BTREE,
  INDEX `idx_consolidado_empleado_periodo`(`employee_id` ASC, `ano` ASC) USING BTREE,
  INDEX `idx_frecuencia_id`(`frecuencia_id` ASC) USING BTREE,
  CONSTRAINT `fk_acumulados_planilla_frecuencia` FOREIGN KEY (`frecuencia_id`) REFERENCES `frecuencias` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 870 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'Acumulados consolidados por empleado/planilla para reportes optimizados' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for admin
-- ----------------------------
DROP TABLE IF EXISTS `admin`;
CREATE TABLE `admin`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `password` varchar(60) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `firstname` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `lastname` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `photo` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `created_on` date NOT NULL,
  `role_id` int NOT NULL,
  `status` tinyint(1) NULL DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 10 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of admin
-- ----------------------------
INSERT INTO `admin` VALUES (1, 'admin', '$2y$10$t.JVHifbU5z7GnwL3/1Z/uMo8jwCwYBD2FHF/j2swOz9BnW.B9NRe', 'Admin', 'Admin', '', '2025-05-12', 1, 1);
INSERT INTO `admin` VALUES (9, 'innova', '$2y$10$vrZ029jSGiZFG5mv5/tO3.L.1zQf9jgnup0inwRscJtSt7D.uwa96', 'innova', 'innova', '', '2025-09-03', 1, 1);

-- ----------------------------
-- Table structure for attendance
-- ----------------------------
DROP TABLE IF EXISTS `attendance`;
CREATE TABLE `attendance`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `date` date NOT NULL,
  `time_in` time NOT NULL,
  `status` int NOT NULL,
  `time_out` time NOT NULL,
  `num_hr` double NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of attendance
-- ----------------------------

-- ----------------------------
-- Table structure for attendance_absence_log
-- ----------------------------
DROP TABLE IF EXISTS `attendance_absence_log`;
CREATE TABLE `attendance_absence_log`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL COMMENT 'FK a tabla employees',
  `absence_date` date NOT NULL COMMENT 'Fecha de la ausencia',
  `absence_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'UNJUSTIFIED' COMMENT 'JUSTIFIED, UNJUSTIFIED, PENDING',
  `justified` tinyint(1) NULL DEFAULT 0 COMMENT 'Ausencia justificada',
  `justification_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'MEDICAL, PERMISSION, VACATION, OTHER',
  `justification_document` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Ruta del documento de justificación',
  `justification_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'Notas de la justificación',
  `is_working_day` tinyint(1) NULL DEFAULT 1 COMMENT 'Era día laboral esperado',
  `day_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'LABORAL, FERIADO, NO_LABORAL',
  `detected_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Cuándo se detectó la ausencia',
  `detection_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'AUTO' COMMENT 'AUTO, MANUAL, SYNC',
  `resolved` tinyint(1) NULL DEFAULT 0 COMMENT 'Ausencia resuelta/justificada',
  `resolved_at` timestamp NULL DEFAULT NULL COMMENT 'Cuándo se resolvió',
  `resolved_by` int NULL DEFAULT NULL COMMENT 'Usuario que resolvió (FK users)',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `unique_employee_absence`(`employee_id` ASC, `absence_date` ASC) USING BTREE,
  INDEX `idx_absence_date`(`absence_date` ASC) USING BTREE,
  INDEX `idx_absence_type`(`absence_type` ASC) USING BTREE,
  INDEX `idx_justified`(`justified` ASC) USING BTREE,
  INDEX `idx_resolved`(`resolved` ASC) USING BTREE,
  INDEX `idx_detected_at`(`detected_at` ASC) USING BTREE,
  CONSTRAINT `attendance_absence_log_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 15 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Registro de ausencias detectadas con justificaciones' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for attendance_alerts
-- ----------------------------
DROP TABLE IF EXISTS `attendance_alerts`;
CREATE TABLE `attendance_alerts`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL COMMENT 'ID del empleado asociado',
  `attendance_id` int NULL DEFAULT NULL COMMENT 'ID de asistencia espec├¡fica (si aplica)',
  `calculation_id` int NULL DEFAULT NULL COMMENT 'ID del c├ílculo de asistencia (si aplica)',
  `alert_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tipo de alerta',
  `severity` enum('INFO','WARNING','CRITICAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'INFO' COMMENT 'Nivel de severidad',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'T├¡tulo descriptivo corto',
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Descripci├│n detallada del problema',
  `article_reference` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Referencia legal (Ej: Art. 31 - C├│digo de Trabajo)',
  `metadata` json NULL COMMENT 'Datos adicionales espec├¡ficos del tipo de alerta',
  `date` date NOT NULL COMMENT 'Fecha principal de la alerta',
  `period_start` date NULL DEFAULT NULL COMMENT 'Fecha inicial del per├¡odo (para alertas de rango)',
  `period_end` date NULL DEFAULT NULL COMMENT 'Fecha final del per├¡odo (para alertas de rango)',
  `status` enum('PENDING','ACKNOWLEDGED','RESOLVED','DISMISSED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING' COMMENT 'Estado de la alerta',
  `acknowledged_by` int NULL DEFAULT NULL COMMENT 'Usuario que reconoci├│ la alerta',
  `acknowledged_at` datetime NULL DEFAULT NULL COMMENT 'Fecha y hora de reconocimiento',
  `resolved_at` datetime NULL DEFAULT NULL COMMENT 'Fecha y hora de resoluci├│n',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'Notas de seguimiento y resoluci├│n',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_employee_id`(`employee_id` ASC) USING BTREE,
  INDEX `idx_attendance_id`(`attendance_id` ASC) USING BTREE,
  INDEX `idx_calculation_id`(`calculation_id` ASC) USING BTREE,
  INDEX `idx_alert_type`(`alert_type` ASC) USING BTREE,
  INDEX `idx_severity`(`severity` ASC) USING BTREE,
  INDEX `idx_status`(`status` ASC) USING BTREE,
  INDEX `idx_date`(`date` ASC) USING BTREE,
  INDEX `idx_employee_status`(`employee_id` ASC, `status` ASC) USING BTREE,
  INDEX `idx_severity_status`(`severity` ASC, `status` ASC) USING BTREE,
  INDEX `idx_date_range`(`period_start` ASC, `period_end` ASC) USING BTREE,
  INDEX `idx_created_at`(`created_at` ASC) USING BTREE,
  INDEX `acknowledged_by`(`acknowledged_by` ASC) USING BTREE,
  INDEX `idx_status_severity_date`(`status` ASC, `severity` ASC, `date` ASC) USING BTREE,
  INDEX `idx_employee_severity_status`(`employee_id` ASC, `severity` ASC, `status` ASC) USING BTREE,
  INDEX `idx_type_date`(`alert_type` ASC, `date` ASC) USING BTREE,
  CONSTRAINT `attendance_alerts_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `attendance_alerts_ibfk_2` FOREIGN KEY (`attendance_id`) REFERENCES `attendance` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `attendance_alerts_ibfk_3` FOREIGN KEY (`calculation_id`) REFERENCES `attendance_calculations` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `attendance_alerts_ibfk_4` FOREIGN KEY (`acknowledged_by`) REFERENCES `admin` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of attendance_alerts
-- ----------------------------

-- ----------------------------
-- Table structure for attendance_api_config
-- ----------------------------
DROP TABLE IF EXISTS `attendance_api_config`;
CREATE TABLE `attendance_api_config`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `api_provider` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'base44' COMMENT 'Proveedor de API (base44, clockify, etc.)',
  `api_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'API Key para autenticación',
  `app_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Application ID',
  `api_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'https://app.base44.com/api' COMMENT 'URL base de la API',
  `sync_enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Sincronización automática habilitada',
  `sync_interval_minutes` int NOT NULL DEFAULT 15 COMMENT 'Intervalo de sincronización en minutos',
  `last_sync_at` timestamp NULL DEFAULT NULL COMMENT 'Timestamp de última sincronización exitosa',
  `last_sync_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'NEVER' COMMENT 'SUCCESS, FAILED, PARTIAL, NEVER',
  `webhook_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'URL del webhook para notificaciones en tiempo real',
  `webhook_secret` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Secret para validar firmas de webhooks',
  `config_json` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'Configuraciones adicionales específicas del proveedor',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_provider`(`api_provider` ASC) USING BTREE,
  INDEX `idx_sync_enabled`(`sync_enabled` ASC) USING BTREE,
  INDEX `idx_last_sync`(`last_sync_at` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Configuración de conexión a API externa de asistencias' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of attendance_api_config
-- ----------------------------
INSERT INTO `attendance_api_config` VALUES (1, 'base44', '40162908d71941b98636b38106be556e', '68dd9181444436f4bd157e1d', 'https://app.base44.com/api', 1, 15, '2025-11-20 10:23:15', 'SUCCESS', '', '', NULL, '2025-10-11 12:46:36', '2025-11-20 10:23:15');

-- ----------------------------
-- Table structure for attendance_calculations
-- ----------------------------
DROP TABLE IF EXISTS `attendance_calculations`;
CREATE TABLE `attendance_calculations`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `attendance_detail_id` int NOT NULL COMMENT 'FK a tabla attendance_detail',
  `employee_id` int NOT NULL COMMENT 'FK a tabla employees',
  `date` date NOT NULL COMMENT 'Fecha de la asistencia',
  `schedule_id` int NULL DEFAULT NULL COMMENT 'Horario asignado al empleado ese día',
  `time_in` time NULL DEFAULT NULL COMMENT 'Hora de entrada real',
  `time_out` time NULL DEFAULT NULL COMMENT 'Hora de salida real',
  `scheduled_time_in` time NULL DEFAULT NULL COMMENT 'Hora de entrada programada',
  `scheduled_time_out` time NULL DEFAULT NULL COMMENT 'Hora de salida programada',
  `total_hours` decimal(5, 2) NULL DEFAULT 0.00 COMMENT 'Total horas trabajadas',
  `regular_hours` decimal(5, 2) NULL DEFAULT 0.00 COMMENT 'Horas regulares',
  `overtime_hours` decimal(5, 2) NULL DEFAULT 0.00 COMMENT 'Horas extras totales',
  `overtime_25_hours` decimal(5, 2) NULL DEFAULT 0.00 COMMENT 'Horas extras al 25%',
  `overtime_50_hours` decimal(5, 2) NULL DEFAULT 0.00 COMMENT 'Horas extras al 50%',
  `overtime_status` enum('PENDING','APPROVED','REJECTED','NOT_APPLICABLE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NOT_APPLICABLE' COMMENT 'Estado de aprobaci├│n de horas extras: PENDING=Pendiente, APPROVED=Aprobada, REJECTED=Rechazada, NOT_APPLICABLE=No hay horas extras',
  `overtime_approved_by` int NULL DEFAULT NULL COMMENT 'ID del usuario que aprobó/rechazó las horas extras',
  `overtime_approved_at` timestamp NULL DEFAULT NULL COMMENT 'Fecha y hora de aprobaci├│n/rechazo',
  `overtime_rejection_reason` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Raz├│n del rechazo (obligatorio si status = REJECTED)',
  `overtime_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'Notas adicionales sobre las horas extras (justificaci├│n, proyecto, etc.)',
  `night_hours` decimal(5, 2) NULL DEFAULT 0.00 COMMENT 'Horas nocturnas (6PM-6AM)',
  `holiday_hours` decimal(5, 2) NULL DEFAULT 0.00 COMMENT 'Horas trabajadas en feriado',
  `tardiness_minutes` int NULL DEFAULT 0 COMMENT 'Minutos de tardanza',
  `is_late` tinyint(1) NULL DEFAULT 0 COMMENT 'Llegó tarde',
  `early_departure_minutes` int NULL DEFAULT 0 COMMENT 'Minutos salida anticipada',
  `is_absent` tinyint(1) NULL DEFAULT 0 COMMENT 'Ausencia completa',
  `absence_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'JUSTIFIED, UNJUSTIFIED, UNKNOWN',
  `is_working_day` tinyint(1) NULL DEFAULT 1 COMMENT 'Es día laboral',
  `is_holiday` tinyint(1) NULL DEFAULT 0 COMMENT 'Es feriado',
  `is_weekend` tinyint(1) NULL DEFAULT 0 COMMENT 'Es fin de semana',
  `day_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'LABORAL, FERIADO, NO_LABORAL',
  `lunch_time_minutes` int NULL DEFAULT 60 COMMENT 'Minutos descontados por almuerzo',
  `is_perfect_attendance` tinyint(1) NULL DEFAULT 0 COMMENT 'Asistencia perfecta (sin tardanza, completo)',
  `punctuality_score` decimal(5, 2) NULL DEFAULT NULL COMMENT 'Score de puntualidad (0-100)',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'Observaciones del cálculo',
  `calculation_details` json NULL COMMENT 'Detalles adicionales en JSON',
  `calculation_version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'v1.0' COMMENT 'Versión del algoritmo de cálculo',
  `calculated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp del cálculo',
  `recalculated_at` timestamp NULL DEFAULT NULL COMMENT 'Última recalculación',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `unique_attendance_detail_calc`(`attendance_detail_id` ASC) USING BTREE,
  INDEX `schedule_id`(`schedule_id` ASC) USING BTREE,
  INDEX `idx_employee_date`(`employee_id` ASC, `date` ASC) USING BTREE,
  INDEX `idx_date`(`date` ASC) USING BTREE,
  INDEX `idx_is_late`(`is_late` ASC) USING BTREE,
  INDEX `idx_is_absent`(`is_absent` ASC) USING BTREE,
  INDEX `idx_is_holiday`(`is_holiday` ASC) USING BTREE,
  INDEX `idx_overtime`(`overtime_hours` ASC) USING BTREE,
  INDEX `idx_calculated_at`(`calculated_at` ASC) USING BTREE,
  INDEX `idx_overtime_pending`(`overtime_status` ASC, `date` ASC, `employee_id` ASC) USING BTREE,
  INDEX `idx_overtime_approved_by`(`overtime_approved_by` ASC, `overtime_approved_at` ASC) USING BTREE,
  INDEX `idx_overtime_status_date`(`overtime_status` ASC, `date` ASC, `overtime_hours` ASC) USING BTREE,
  CONSTRAINT `attendance_calculations_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `attendance_calculations_ibfk_3` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `fk_attendance_calc_approved_by` FOREIGN KEY (`overtime_approved_by`) REFERENCES `admin` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_attendance_detail` FOREIGN KEY (`attendance_detail_id`) REFERENCES `attendance_detail` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 37 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Cálculos avanzados de asistencias con métricas detalladas' ROW_FORMAT = Dynamic;


-- ----------------------------
-- Table structure for attendance_concepts_mapping
-- ----------------------------
DROP TABLE IF EXISTS `attendance_concepts_mapping`;
CREATE TABLE `attendance_concepts_mapping`  (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'ID ├║nico del mapeo',
  `mapping_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nombre descriptivo del mapeo',
  `mapping_type` enum('HORAS_TRABAJADAS','HORAS_EXTRAS_25','HORAS_EXTRAS_50','HORAS_NOCTURNAS','HORAS_FERIADOS','HORAS_DOMINICALES','DESCUENTO_TARDANZAS','DESCUENTO_AUSENCIAS','BONO_PUNTUALIDAD','BONO_ASISTENCIA_PERFECTA') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tipo de c├ílculo de asistencia',
  `concepto_id` int NOT NULL COMMENT 'ID del concepto destino en tabla concepto',
  `formula_multiplicador` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'F├│rmula para calcular multiplicador (ej: tarifa_hora * 1.25)',
  `valor_fijo` decimal(10, 2) NULL DEFAULT NULL COMMENT 'Valor fijo por unidad (si aplica)',
  `usar_referencia_valor` tinyint(1) NULL DEFAULT 1 COMMENT 'Si debe guardarse en referencia_valor de planilla_detalle',
  `tipo_planilla_id` int NULL DEFAULT NULL COMMENT 'Solo aplica a este tipo de planilla (NULL = todos)',
  `situacion_id` int NULL DEFAULT NULL COMMENT 'Solo aplica a esta situaci├│n (NULL = todos)',
  `requiere_aprobacion` tinyint(1) NULL DEFAULT 0 COMMENT 'Requiere aprobaci├│n manual antes de aplicar',
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'Descripci├│n detallada del mapeo',
  `notas` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'Notas adicionales o reglas especiales',
  `is_active` tinyint(1) NULL DEFAULT 1 COMMENT 'Si el mapeo est├í activo',
  `priority` int NULL DEFAULT 100 COMMENT 'Prioridad de aplicaci├│n (menor = m├ís prioritario)',
  `created_by` int NULL DEFAULT NULL COMMENT 'Usuario que cre├│ el mapeo',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_mapping_type`(`mapping_type` ASC) USING BTREE,
  INDEX `idx_concepto`(`concepto_id` ASC) USING BTREE,
  INDEX `idx_tipo_planilla`(`tipo_planilla_id` ASC) USING BTREE,
  INDEX `idx_active_priority`(`is_active` ASC, `priority` ASC) USING BTREE,
  INDEX `fk_mapping_situacion`(`situacion_id` ASC) USING BTREE,
  CONSTRAINT `fk_mapping_concepto` FOREIGN KEY (`concepto_id`) REFERENCES `concepto` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_mapping_situacion` FOREIGN KEY (`situacion_id`) REFERENCES `situaciones` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_mapping_tipo_planilla` FOREIGN KEY (`tipo_planilla_id`) REFERENCES `tipos_planilla` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Mapeo din├ímico entre c├ílculos de asistencia y conceptos de planilla' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of attendance_concepts_mapping
-- ----------------------------

-- ----------------------------
-- Table structure for attendance_detail
-- ----------------------------
DROP TABLE IF EXISTS `attendance_detail`;
CREATE TABLE `attendance_detail`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `header_id` int NOT NULL,
  `employee_id` int NOT NULL,
  `schedule_id` int NULL DEFAULT NULL,
  `time_in` time NULL DEFAULT NULL,
  `time_out` time NULL DEFAULT NULL,
  `lunch_out` datetime NULL DEFAULT NULL COMMENT 'Fecha/hora real de salida a almuerzo',
  `lunch_in` datetime NULL DEFAULT NULL COMMENT 'Fecha/hora real de entrada despu├®s de almuerzo',
  `scheduled_lunch_out` time NULL DEFAULT NULL COMMENT 'Hora programada de salida a almuerzo (del schedule)',
  `scheduled_lunch_in` time NULL DEFAULT NULL COMMENT 'Hora programada de entrada despu├®s de almuerzo (del schedule)',
  `lunch_duration_minutes` int NULL DEFAULT 0 COMMENT 'Duraci├│n real del almuerzo en minutos',
  `lunch_exceeded_minutes` int NULL DEFAULT 0 COMMENT 'Minutos de exceso en el per├¡odo de almuerzo',
  `scheduled_time_in` time NULL DEFAULT NULL,
  `scheduled_time_out` time NULL DEFAULT NULL,
  `device_id` int NULL DEFAULT NULL,
  `external_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `tardiness_minutes` int NULL DEFAULT 0,
  `is_late` tinyint(1) NULL DEFAULT 0,
  `early_departure_minutes` int NULL DEFAULT 0,
  `hours_worked` decimal(5, 2) NULL DEFAULT 0.00,
  `status` enum('PRESENT','ABSENT','LATE','INCOMPLETE','JUSTIFIED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'PRESENT',
  `justification_type` enum('MEDICAL','PERMISSION','VACATION','OTHER') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `justification_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `justification_document` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `unique_header_employee`(`header_id` ASC, `employee_id` ASC) USING BTREE,
  INDEX `idx_header`(`header_id` ASC) USING BTREE,
  INDEX `idx_employee`(`employee_id` ASC) USING BTREE,
  INDEX `idx_device`(`device_id` ASC) USING BTREE,
  INDEX `idx_status`(`status` ASC) USING BTREE,
  INDEX `idx_is_late`(`is_late` ASC) USING BTREE,
  INDEX `schedule_id`(`schedule_id` ASC) USING BTREE,
  INDEX `idx_detail_composite`(`header_id` ASC, `employee_id` ASC, `status` ASC) USING BTREE,
  INDEX `idx_employee_date`(`employee_id` ASC, `header_id` ASC) USING BTREE,
  INDEX `idx_lunch_times`(`lunch_out` ASC, `lunch_in` ASC) USING BTREE,
  INDEX `idx_lunch_duration`(`lunch_duration_minutes` ASC) USING BTREE,
  CONSTRAINT `attendance_detail_ibfk_1` FOREIGN KEY (`header_id`) REFERENCES `attendance_header` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `attendance_detail_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `attendance_detail_ibfk_3` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `attendance_detail_ibfk_4` FOREIGN KEY (`device_id`) REFERENCES `attendance_devices` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 56 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for attendance_devices
-- ----------------------------
DROP TABLE IF EXISTS `attendance_devices`;
CREATE TABLE `attendance_devices`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `device_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `device_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `device_type` enum('API','BIOMETRIC','TEXT_FILE','MANUAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'API',
  `location` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `is_active` tinyint(1) NULL DEFAULT 1,
  `config_json` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `api_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `api_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `file_format` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `delimiter` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT ',',
  `date_format` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'Y-m-d',
  `time_format` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'H:i:s',
  `column_mapping` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `device_code`(`device_code` ASC) USING BTREE,
  INDEX `idx_device_code`(`device_code` ASC) USING BTREE,
  INDEX `idx_device_type`(`device_type` ASC) USING BTREE,
  INDEX `idx_is_active`(`is_active` ASC) USING BTREE,
  INDEX `created_by`(`created_by` ASC) USING BTREE,
  INDEX `idx_device_active`(`is_active` ASC, `device_type` ASC) USING BTREE,
  CONSTRAINT `attendance_devices_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admin` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of attendance_devices
-- ----------------------------
INSERT INTO `attendance_devices` VALUES (1, 'API', 'API BASE44', 'API', 'API', 1, NULL, '', '', NULL, ',', 'Y-m-d', 'H:i:s', NULL, '2025-10-27 15:42:38', '2025-10-27 15:42:38', NULL);

-- ----------------------------
-- Table structure for attendance_file_imports
-- ----------------------------
DROP TABLE IF EXISTS `attendance_file_imports`;
CREATE TABLE `attendance_file_imports`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `device_id` int NOT NULL,
  `filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `file_size` int NULL DEFAULT NULL,
  `total_lines` int NULL DEFAULT 0,
  `successful_imports` int NULL DEFAULT 0,
  `failed_imports` int NULL DEFAULT 0,
  `skipped_lines` int NULL DEFAULT 0,
  `date_from` date NULL DEFAULT NULL,
  `date_to` date NULL DEFAULT NULL,
  `status` enum('PENDING','PROCESSING','COMPLETED','FAILED','PARTIAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'PENDING',
  `error_log` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `success_log` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `processing_started_at` timestamp NULL DEFAULT NULL,
  `processing_completed_at` timestamp NULL DEFAULT NULL,
  `processing_duration` int NULL DEFAULT NULL,
  `imported_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `imported_by` int NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_device`(`device_id` ASC) USING BTREE,
  INDEX `idx_status`(`status` ASC) USING BTREE,
  INDEX `idx_imported_at`(`imported_at` ASC) USING BTREE,
  INDEX `idx_date_range`(`date_from` ASC, `date_to` ASC) USING BTREE,
  INDEX `imported_by`(`imported_by` ASC) USING BTREE,
  CONSTRAINT `attendance_file_imports_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `attendance_devices` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `attendance_file_imports_ibfk_2` FOREIGN KEY (`imported_by`) REFERENCES `admin` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of attendance_file_imports
-- ----------------------------

-- ----------------------------
-- Table structure for attendance_header
-- ----------------------------
DROP TABLE IF EXISTS `attendance_header`;
CREATE TABLE `attendance_header`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `attendance_date` date NOT NULL,
  `device_id` int NULL DEFAULT NULL,
  `total_records` int NULL DEFAULT 0,
  `total_employees` int NULL DEFAULT 0,
  `total_on_time` int NULL DEFAULT 0,
  `total_late` int NULL DEFAULT 0,
  `total_absent` int NULL DEFAULT 0,
  `is_processed` tinyint(1) NULL DEFAULT 0,
  `processed_at` timestamp NULL DEFAULT NULL,
  `processed_by` int NULL DEFAULT NULL,
  `sync_batch_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `synced_from` enum('API','FILE','MANUAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'API',
  `file_import_id` int NULL DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `unique_date_device`(`attendance_date` ASC, `device_id` ASC) USING BTREE,
  INDEX `idx_attendance_date`(`attendance_date` ASC) USING BTREE,
  INDEX `idx_processed`(`is_processed` ASC) USING BTREE,
  INDEX `idx_synced_from`(`synced_from` ASC) USING BTREE,
  INDEX `idx_sync_batch`(`sync_batch_id` ASC) USING BTREE,
  INDEX `device_id`(`device_id` ASC) USING BTREE,
  INDEX `processed_by`(`processed_by` ASC) USING BTREE,
  CONSTRAINT `attendance_header_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `attendance_devices` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `attendance_header_ibfk_2` FOREIGN KEY (`processed_by`) REFERENCES `admin` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 19 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for attendance_raw_data
-- ----------------------------
DROP TABLE IF EXISTS `attendance_raw_data`;
CREATE TABLE `attendance_raw_data`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `external_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'ID de la entidad desde API externa',
  `api_provider` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'base44' COMMENT 'Proveedor de API',
  `entity_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tipo de entidad: Employee, Attendance',
  `raw_json` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Payload completo de la API en formato JSON',
  `processed` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Indica si el registro ya fue procesado',
  `processed_at` timestamp NULL DEFAULT NULL COMMENT 'Timestamp de procesamiento',
  `processing_error` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'Error durante el procesamiento (si existe)',
  `received_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp de recepción desde API',
  `sync_batch_id` int NULL DEFAULT NULL COMMENT 'ID del batch de sincronización',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_external_id`(`external_id` ASC) USING BTREE,
  INDEX `idx_provider`(`api_provider` ASC) USING BTREE,
  INDEX `idx_entity_type`(`entity_type` ASC) USING BTREE,
  INDEX `idx_processed`(`processed` ASC) USING BTREE,
  INDEX `idx_received_at`(`received_at` ASC) USING BTREE,
  INDEX `idx_sync_batch`(`sync_batch_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 184 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Backup de datos crudos desde API externa para auditoría' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for attendance_records
-- ----------------------------
DROP TABLE IF EXISTS `attendance_records`;
CREATE TABLE `attendance_records`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `raw_data_id` int NULL DEFAULT NULL COMMENT 'FK a attendance_raw_data - Origen del registro',
  `external_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'ID del registro en sistema externo (API/dispositivo)',
  `employee_id` int NOT NULL COMMENT 'FK a employees - Empleado que realizó la marcación',
  `timestamp` datetime NOT NULL COMMENT 'Fecha y hora exacta de la marcación',
  `punch_date` date NOT NULL COMMENT 'Fecha de la marcación (para agrupación)',
  `punch_time` time NOT NULL COMMENT 'Hora de la marcación',
  `punch_type` enum('CHECK_IN','CHECK_OUT') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tipo de marcación',
  `device_id` int NULL DEFAULT NULL COMMENT 'FK a attendance_devices - Dispositivo que registró',
  `device_serial` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Serial del dispositivo físico',
  `source` enum('API','TXT','MANUAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'API' COMMENT 'Origen de la marcación',
  `is_processed` tinyint(1) NULL DEFAULT 0 COMMENT '¿Ya se consolidó en attendance_detail?',
  `processed_at` timestamp NULL DEFAULT NULL COMMENT 'Cuándo se procesó a detail',
  `detail_id` int NULL DEFAULT NULL COMMENT 'FK a attendance_detail al que pertenece',
  `is_duplicate` tinyint(1) NULL DEFAULT 0 COMMENT 'Marcación duplicada detectada',
  `duplicate_of` int NULL DEFAULT NULL COMMENT 'ID del registro original del cual es duplicado',
  `record_hash` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Hash único para deduplicación',
  `metadata` json NULL COMMENT 'Datos adicionales del API o dispositivo',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'Notas adicionales sobre la marcación',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `record_hash`(`record_hash` ASC) USING BTREE,
  INDEX `idx_employee`(`employee_id` ASC) USING BTREE,
  INDEX `idx_date`(`punch_date` ASC) USING BTREE,
  INDEX `idx_timestamp`(`timestamp` ASC) USING BTREE,
  INDEX `idx_processed`(`is_processed` ASC) USING BTREE,
  INDEX `idx_duplicate`(`is_duplicate` ASC) USING BTREE,
  INDEX `idx_external_id`(`external_id` ASC) USING BTREE,
  INDEX `idx_source`(`source` ASC) USING BTREE,
  INDEX `idx_device`(`device_id` ASC) USING BTREE,
  INDEX `idx_detail`(`detail_id` ASC) USING BTREE,
  INDEX `idx_employee_date`(`employee_id` ASC, `punch_date` ASC) USING BTREE,
  INDEX `idx_employee_date_type`(`employee_id` ASC, `punch_date` ASC, `punch_type` ASC) USING BTREE,
  INDEX `idx_date_processed`(`punch_date` ASC, `is_processed` ASC) USING BTREE,
  INDEX `fk_records_raw_data`(`raw_data_id` ASC) USING BTREE,
  INDEX `fk_records_duplicate`(`duplicate_of` ASC) USING BTREE,
  CONSTRAINT `fk_records_detail` FOREIGN KEY (`detail_id`) REFERENCES `attendance_detail` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `fk_records_device` FOREIGN KEY (`device_id`) REFERENCES `attendance_devices` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `fk_records_duplicate` FOREIGN KEY (`duplicate_of`) REFERENCES `attendance_records` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `fk_records_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_records_raw_data` FOREIGN KEY (`raw_data_id`) REFERENCES `attendance_raw_data` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 47 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Marcaciones individuales normalizadas (capa intermedia)' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for attendance_sync_log
-- ----------------------------
DROP TABLE IF EXISTS `attendance_sync_log`;
CREATE TABLE `attendance_sync_log`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `sync_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'FULL, INCREMENTAL, MANUAL, WEBHOOK',
  `start_time` timestamp NOT NULL COMMENT 'Inicio de la sincronización',
  `end_time` timestamp NULL DEFAULT NULL COMMENT 'Fin de la sincronización',
  `duration_seconds` int NULL DEFAULT NULL COMMENT 'Duración en segundos',
  `records_fetched` int NULL DEFAULT 0 COMMENT 'Registros obtenidos desde API',
  `records_inserted` int NULL DEFAULT 0 COMMENT 'Registros insertados en BD local',
  `records_updated` int NULL DEFAULT 0 COMMENT 'Registros actualizados en BD local',
  `records_skipped` int NULL DEFAULT 0 COMMENT 'Registros omitidos (duplicados/inválidos)',
  `errors_count` int NULL DEFAULT 0 COMMENT 'Número de errores encontrados',
  `error_details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'Detalles de errores en formato JSON',
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'RUNNING' COMMENT 'RUNNING, SUCCESS, FAILED, PARTIAL',
  `status_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'Mensaje descriptivo del resultado',
  `triggered_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'CRON, USER_ID:123, WEBHOOK',
  `api_provider` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'base44',
  `filters_json` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'Filtros aplicados durante la sincronización (JSON)',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_sync_type`(`sync_type` ASC) USING BTREE,
  INDEX `idx_status`(`status` ASC) USING BTREE,
  INDEX `idx_start_time`(`start_time` ASC) USING BTREE,
  INDEX `idx_api_provider`(`api_provider` ASC) USING BTREE,
  INDEX `idx_triggered_by`(`triggered_by` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 9 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Historial de sincronizaciones con API externa' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for business_calendar
-- ----------------------------
DROP TABLE IF EXISTS `business_calendar`;
CREATE TABLE `business_calendar`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `date_value` date NOT NULL,
  `day_type` enum('LABORAL','NO_LABORAL','FERIADO','DUELO_NACIONAL','ESPECIAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'LABORAL',
  `status` enum('NORMAL','RECUPERABLE','MEDIO_DIA','HORARIO_ESPECIAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NORMAL',
  `is_paid_holiday` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Indica si el feriado es pagado por el empleador (genera 8 horas en asistencias)',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `is_weekend` tinyint(1) NULL DEFAULT 0,
  `year_value` year NOT NULL,
  `month_value` tinyint NOT NULL,
  `day_of_week` tinyint NOT NULL COMMENT '1=Lunes, 7=Domingo',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `date_value`(`date_value` ASC) USING BTREE,
  INDEX `idx_date`(`date_value` ASC) USING BTREE,
  INDEX `idx_year_month`(`year_value` ASC, `month_value` ASC) USING BTREE,
  INDEX `idx_day_type`(`day_type` ASC) USING BTREE,
  INDEX `idx_status`(`status` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 367 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for calendar_sync_log
-- ----------------------------
DROP TABLE IF EXISTS `calendar_sync_log`;
CREATE TABLE `calendar_sync_log`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `sync_type` enum('MANUAL','SCHEDULED','WEBHOOK') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MANUAL' COMMENT 'Tipo de sincronizaci├│n',
  `start_time` datetime NOT NULL COMMENT 'Hora de inicio de sincronizaci├│n',
  `end_time` datetime NULL DEFAULT NULL COMMENT 'Hora de fin de sincronizaci├│n',
  `duration_seconds` int UNSIGNED NULL DEFAULT NULL COMMENT 'Duraci├│n en segundos',
  `status` enum('RUNNING','SUCCESS','FAILED','PARTIAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'RUNNING' COMMENT 'Estado de la sincronizaci├│n',
  `status_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'Mensaje descriptivo del estado',
  `triggered_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Usuario o proceso que inici├│ la sincronizaci├│n',
  `records_fetched` int UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Total de registros obtenidos desde API',
  `records_inserted` int UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Total de registros insertados',
  `records_updated` int UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Total de registros actualizados',
  `records_skipped` int UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Total de registros omitidos',
  `records_deleted` int UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Total de registros eliminados (modo replace)',
  `errors_count` int UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Total de errores encontrados',
  `error_details` json NULL COMMENT 'Detalles de errores en formato JSON',
  `options_json` json NULL COMMENT 'Opciones de sincronizaci├│n (year, replace, dry_run)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_sync_type`(`sync_type` ASC) USING BTREE,
  INDEX `idx_status`(`status` ASC) USING BTREE,
  INDEX `idx_start_time`(`start_time` ASC) USING BTREE,
  INDEX `idx_triggered_by`(`triggered_by` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Log de sincronizaciones del calendario empresarial' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of calendar_sync_log
-- ----------------------------

-- ----------------------------
-- Table structure for cargos
-- ----------------------------
DROP TABLE IF EXISTS `cargos`;
CREATE TABLE `cargos`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `unique_codigo`(`codigo` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 8 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of cargos
-- ----------------------------
INSERT INTO `cargos` VALUES (1, 'CAR-001', 'SALES MANAGER', 'Gerente de Ventas', 1, '2025-09-10 02:17:23', '2025-09-12 18:56:18');
INSERT INTO `cargos` VALUES (2, 'CAR-002', 'HELP DESK SUPPORT', 'Agente de Soporte Técnico', 1, '2025-09-11 18:42:43', '2025-09-12 18:55:53');
INSERT INTO `cargos` VALUES (3, 'CAR-003', 'HELP DESK MANAGER', 'Gerente de Soporte Técnico', 1, '2025-09-12 18:47:25', '2025-09-12 18:55:06');
INSERT INTO `cargos` VALUES (5, 'CAR-004', 'ADMINISTRATIVE ASSISTANT', 'Asistente Administrativo', 1, '2025-09-12 18:54:16', '2025-09-12 18:56:06');
INSERT INTO `cargos` VALUES (6, 'CAR-005', 'CEO', 'Gerente General', 1, '2025-09-12 19:06:46', '2025-09-12 19:06:46');
INSERT INTO `cargos` VALUES (7, 'CAR-006', 'PROGRAMADOR SENIOR', 'Programador Junior', 1, '2025-09-12 19:10:47', '2025-09-12 19:10:47');

-- ----------------------------
-- Table structure for cashadvance
-- ----------------------------
DROP TABLE IF EXISTS `cashadvance`;
CREATE TABLE `cashadvance`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `date_advance` date NOT NULL,
  `employee_id` varchar(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `amount` double NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of cashadvance
-- ----------------------------

-- ----------------------------
-- Table structure for companies
-- ----------------------------
DROP TABLE IF EXISTS `companies`;
CREATE TABLE `companies`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ruc` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `legal_representative` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `currency_symbol` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'B/.',
  `currency_code` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'PAB',
  `logo_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `tipo_institucion` enum('publica','privada') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'privada',
  `jefe_recursos_humanos` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'Nombre del jefe de recursos humanos para firmas en reportes',
  `cargo_jefe_rrhh` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'Jefe de Recursos Humanos' COMMENT 'Cargo del jefe de RRHH',
  `elaborado_por` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'Nombre de quien elabora la planilla para firmas en reportes',
  `cargo_elaborador` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'Especialista en Nominas' COMMENT 'Cargo de quien elabora la planilla',
  `firma_director_planilla` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `cargo_director_planilla` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'Director General',
  `firma_contador_planilla` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `cargo_contador_planilla` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'Contador General',
  `logo_empresa` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'Ruta del logo principal de la empresa',
  `logo_izquierdo_reportes` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'Ruta del logo izquierdo para reportes PDF/Excel',
  `logo_derecho_reportes` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'Ruta del logo derecho para reportes PDF/Excel',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'Tabla de configuración empresarial con logos para reportes' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of companies
-- ----------------------------
INSERT INTO `companies` VALUES (1, 'Company SA', '2225999-1-777999', 'REPRESENTANTE LEGAL', 'PANAMA', '999-9999', 'administracion@nmspanama.com', 'B/.', 'PAB', NULL, 'privada', 'Jefe de Recursos Humanos', 'Jefe de Recursos Humanos', 'Especialista en Nominas', 'Especialista en Nominas', NULL, 'Director General', NULL, 'Contador General', 'logo_empresa_1761936623.png', 'logo_izquierdo_reportes_1761936630.jpg', '');

-- ----------------------------
-- Table structure for concepto
-- ----------------------------
DROP TABLE IF EXISTS `concepto`;
CREATE TABLE `concepto`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `concepto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `cuenta_contable` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `tipo_concepto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `unidad` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `tipos_planilla` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `frecuencias` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `situaciones` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `formula` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `valor_fijo` decimal(10, 2) NULL DEFAULT NULL,
  `imprime_detalles` int NULL DEFAULT NULL,
  `prorratea` int NULL DEFAULT NULL,
  `modifica_valor` int NULL DEFAULT NULL,
  `valor_referencia` int NULL DEFAULT NULL,
  `monto_calculo` int NULL DEFAULT NULL,
  `monto_cero` int NULL DEFAULT NULL,
  `incluir_reporte` tinyint(1) NULL DEFAULT 1 COMMENT 'Si debe incluirse en reportes PDF',
  `categoria_reporte` enum('seguro_social','seguro_educativo','impuesto_renta','otras_deducciones','liquidacion','otro') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'otro',
  `orden_reporte` int NULL DEFAULT 0 COMMENT 'Orden de aparición en el reporte',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 62 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of concepto
-- ----------------------------
INSERT INTO `concepto` VALUES (1, '01', 'Sueldo', '', 'A', 'monto', '1', '5', '1', 'SUELDO*0.5', NULL, 1, 1, 1, 1, 1, 0, 1, 'otro', 0);
INSERT INTO `concepto` VALUES (2, '02', 'Seguro Social', '', 'D', 'monto', '1,2', '1,2,11', '1', 'SI(CONCEPTO(\"VACACIONES\") > 0, CONCEPTO(\"VACACIONES\") * 0.0975, (SUELDO/2)*0.0975)', NULL, 1, 1, 1, 1, 1, 1, 1, 'seguro_social', 1);
INSERT INTO `concepto` VALUES (3, '03', 'Seguro Educativo', '', 'D', 'monto', '1,2', '1,2,11', '1', 'SI(CONCEPTO(\"VACACIONES\") > 0, CONCEPTO(\"VACACIONES\") * 0.0125, (SUELDO/2)*0.0125)', NULL, 1, 1, 1, 1, 1, 0, 1, 'seguro_educativo', 2);
INSERT INTO `concepto` VALUES (4, '4', 'Impuesto sobre renta', '', 'D', 'monto', '2,3', '2,3', '1', 'SUELDO*0.025', NULL, 1, 1, 1, 1, 1, 0, 1, 'impuesto_renta', 3);
INSERT INTO `concepto` VALUES (18, '5', 'Seguro Social Patronal', '', 'P', 'monto', NULL, NULL, NULL, '(SALARIO/2)*0.1325', NULL, 0, 1, 1, 0, 1, 0, 1, 'otro', 0);
INSERT INTO `concepto` VALUES (19, '6', 'Seguro Educativo Patronal', '', 'P', 'monto', NULL, NULL, NULL, '(SALARIO/2)*0.015', NULL, 0, 1, 1, 1, 1, 0, 1, 'otro', 0);
INSERT INTO `concepto` VALUES (20, '7', 'Seguro Riesgo Profesional patronal', '', 'P', 'monto', NULL, NULL, NULL, '(SALARIO/2)*0.021', NULL, 0, 1, 1, 1, 1, 0, 1, 'otro', 0);
INSERT INTO `concepto` VALUES (21, '8', 'Décimo Tercer Mes', '', 'A', 'monto', NULL, NULL, NULL, 'dias_trabajados = ANTIGUEDAD_DIAS\r\nacumulados = ACUMULADOS(\"SALARIO_BASE,HORAS_EXTRAS,COMISIONES,BONIFICACIONES\", FICHA, INIPERIODO, FINPERIODO) \r\nacumulados/12', NULL, 1, 1, 1, 1, 1, 0, 1, 'otro', 0);
INSERT INTO `concepto` VALUES (22, 'SS_XIII', 'SEGURO SOCIAL DECIMO TERCER MES', '', 'D', 'monto', NULL, NULL, NULL, 'dias_trabajados = ANTIGUEDAD_DIAS\r\nacumulados = ACUMULADOS(\"SALARIO_BASE,HORAS_EXTRAS,COMISIONES,BONIFICACIONES\", FICHA, INIPERIODO, FINPERIODO) \r\nmonto=acumulados/12\r\nmonto*0.0725', NULL, 1, 1, 1, 1, 1, 0, 1, 'otro', 0);
INSERT INTO `concepto` VALUES (23, '10', 'Impuesto sobre renta XIII Mes', '', 'D', 'monto', NULL, NULL, NULL, 'SALARIOBRUTOACUMULADO', NULL, 1, 1, 1, 1, 1, 0, 1, 'otro', 0);
INSERT INTO `concepto` VALUES (24, '11', 'Gasto de replantación Seguro Social', '', 'D', 'monto', NULL, NULL, NULL, 'TOTALGASTODEREPRESENTAACION', NULL, 1, 1, 1, 1, 1, 0, 1, 'otro', 0);
INSERT INTO `concepto` VALUES (25, 'LIQ001', 'Prima de Antigüedad', '', 'A', 'monto', NULL, NULL, NULL, 'acumulados = ACUMULADOS(\"SALARIO_BASE\", FICHA, INIPERIODO, FINPERIODO)\r\nvac_prop = CONCEPTO(\"LIQ005\")\r\n(acumulados+vac_prop)*0.01923', NULL, 1, 1, 1, 1, 1, 0, 1, 'liquidacion', 0);
INSERT INTO `concepto` VALUES (26, 'LIQ002', 'Indemnización por Despido', '', 'A', 'monto', NULL, NULL, NULL, 'acumulados =ACUMULADOS(\"SALARIO_BASE\", FICHA,INIPERIODO, FINPERIODO)\r\nacumulados * 0.0654', NULL, 0, 0, 0, 0, 0, 0, 1, 'liquidacion', 0);
INSERT INTO `concepto` VALUES (28, 'LIQ004', 'Vacaciones Proporcionales (9.0909)', '', 'A', 'monto', NULL, NULL, NULL, 'acumulados = ACUMULADOS(\"SALARIO_MENS\", FICHA, INIPERIODO, FINPERIODO)\r\nacumulados/11', NULL, 1, 1, 1, 1, 1, 0, 1, 'liquidacion', 0);
INSERT INTO `concepto` VALUES (42, 'LIQ007', 'XIII PROPORCIONAL', '', 'A', 'monto', NULL, NULL, NULL, 'acumulados = ACUMULADOS(\"SALARIO_BASE\", FICHA, INICIO_PERIODO_XIII, FIN_PERIODO_XIII)+CONCEPTO(\"LIQ005\")\r\nacumulados/12', NULL, 1, 1, 1, 1, 1, 0, 1, 'liquidacion', 0);
INSERT INTO `concepto` VALUES (43, 'LIQ005', 'Vacaciones Proporcionales', '', 'A', 'monto', NULL, NULL, NULL, 'acumulados = ACUMULADOS(\"SALARIO_BASE\", FICHA, INIPERIODO, FINPERIODO)\r\nacumulados/11', NULL, 1, 1, 1, 1, 1, 0, 1, 'liquidacion', 0);
INSERT INTO `concepto` VALUES (45, 'LIQ008', 'Descuento SS - Vacaciones', '', 'D', 'monto', NULL, NULL, NULL, 'CONCEPTO(\"LIQ005\") * 0.0975', NULL, 0, 0, 0, 0, 0, 0, 1, 'liquidacion', 0);
INSERT INTO `concepto` VALUES (46, 'LIQ011', 'Descuento SS - XIII Mes', '', 'D', 'monto', NULL, NULL, NULL, 'CONCEPTO(\"LIQ007\") * 0.0725', NULL, 1, 1, 1, 1, 1, 0, 1, 'liquidacion', 0);
INSERT INTO `concepto` VALUES (47, 'LIQ010', 'Preaviso', '', 'A', 'monto', NULL, NULL, NULL, 'SUELDO_DIARIO * DIAS_PREAVISO', NULL, 1, 1, 1, 0, 1, 0, 1, 'liquidacion', 0);
INSERT INTO `concepto` VALUES (48, 'LIQ009', 'Descuento SE - Vacaciones PROP', '', 'D', 'monto', NULL, NULL, NULL, 'CONCEPTO(\"LIQ005\") * 0.0125', NULL, 1, 1, 1, 1, 1, 0, 1, 'liquidacion', 0);
INSERT INTO `concepto` VALUES (49, 'LIQ012', 'Descuento ISR - XIII MES PROP', '', 'D', 'monto', NULL, NULL, NULL, 'CONCEPTO(\"LIQ006\") * 0.0975', NULL, 1, 1, 1, 1, 1, 0, 1, 'liquidacion', 0);
INSERT INTO `concepto` VALUES (50, 'SP', 'SERVICIOS PROFESIONALES', '', 'A', 'monto', NULL, NULL, NULL, 'SI(MARCA_ASISTENCIA, HORAS_REGULARES()* TARIFA_HORA, SUELDO*0.5)', NULL, 1, 1, 1, 1, 1, 0, 1, 'otro', 0);
INSERT INTO `concepto` VALUES (51, 'CADESE', 'DEDUCCIÓN CAJA DE SEGURO SOCIAL', '', 'D', 'monto', NULL, NULL, NULL, 'ACREEDOR(EMPLEADO, 2)', NULL, 1, 1, 1, 1, 1, 0, 1, 'otro', 0);
INSERT INTO `concepto` VALUES (52, 'DEPL', 'DEDUCCIÓN DESCUENTO PLANILLA', NULL, 'D', NULL, NULL, NULL, NULL, 'ACREEDOR(EMPLEADO, 3)', NULL, 1, NULL, NULL, NULL, 1, 1, 1, 'otro', 0);
INSERT INTO `concepto` VALUES (53, 'HORAS_TRABAJADAS', 'HORAS TRABAJADAS', '', 'A', 'monto', '1', '5', '1', 'SI(MARCA_ASISTENCIA, HORAS_REGULARES()* TARIFA_HORA, SUELDO*0.5)', NULL, 1, 1, 1, 1, 1, 0, 1, 'otro', 0);
INSERT INTO `concepto` VALUES (54, 'HORAS_REGULARES', 'HORAS_REGULARES', '', 'A', 'monto', '1', '5', '1', 'SUELDO*0.5', NULL, 1, 1, 1, 1, 1, 0, 1, 'otro', 0);
INSERT INTO `concepto` VALUES (55, 'HORAS_EXTRAS', 'HORAS_EXTRAS', '', 'A', 'monto', '1', '5', '1', 'SUELDO*0.5', NULL, 1, 1, 1, 1, 1, 0, 1, 'otro', 0);
INSERT INTO `concepto` VALUES (56, 'HORAS_EXTRAS_25', 'HORAS_EXTRAS_25', '', 'A', 'monto', '1', '5', '1', 'SUELDO*0.5', NULL, 1, 1, 1, 1, 1, 0, 1, 'otro', 0);
INSERT INTO `concepto` VALUES (57, 'HORAS_EXTRAS_50', 'HORAS_EXTRAS_50', '', 'A', 'monto', '1', '5', '1', 'SUELDO*0.5', NULL, 1, 1, 1, 1, 1, 0, 1, 'otro', 0);
INSERT INTO `concepto` VALUES (58, 'HORAS_NOCTURNAS', 'HORAS_NOCTURNAS', '', 'A', 'monto', '1', '5', '1', 'SUELDO*0.5', NULL, 1, 1, 1, 1, 1, 0, 1, 'otro', 0);
INSERT INTO `concepto` VALUES (59, 'HORAS_FERIADOS', 'HORAS_FERIADOS', '', 'A', 'monto', '1', '5', '1', 'HORAS_FERIADOS() * (SUELDO / 220) * 1.5', NULL, 1, 1, 1, 1, 1, 0, 1, 'otro', 0);
INSERT INTO `concepto` VALUES (60, 'HORAS_DOMINICALES', 'HORAS_DOMINICALES', '', 'A', 'monto', NULL, NULL, NULL, 'HORAS_DOMINICALES() * (SUELDO / 220) * 1.5', NULL, 0, 0, 0, 0, 0, 0, 1, 'otro', 0);
INSERT INTO `concepto` VALUES (61, 'VACACIONES', 'Pago de Vacaciones', NULL, 'A', NULL, '', '11', NULL, 'ACUMULADOS(\"SALARIO_BASE\", INIPERIODO, FINPERIODO) / 11 / 30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'otro', 0);

-- ----------------------------
-- Table structure for concepto_frecuencias
-- ----------------------------
DROP TABLE IF EXISTS `concepto_frecuencias`;
CREATE TABLE `concepto_frecuencias`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `concepto_id` int NOT NULL,
  `frecuencia_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `unique_concepto_frecuencia`(`concepto_id` ASC, `frecuencia_id` ASC) USING BTREE,
  INDEX `frecuencia_id`(`frecuencia_id` ASC) USING BTREE,
  CONSTRAINT `concepto_frecuencias_ibfk_1` FOREIGN KEY (`concepto_id`) REFERENCES `concepto` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `concepto_frecuencias_ibfk_2` FOREIGN KEY (`frecuencia_id`) REFERENCES `frecuencias` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 225 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ------------------
-- ----------------------------
-- Table structure for concepto_situaciones
-- ----------------------------
DROP TABLE IF EXISTS `concepto_situaciones`;
CREATE TABLE `concepto_situaciones`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `concepto_id` int NOT NULL,
  `situacion_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `unique_concepto_situacion`(`concepto_id` ASC, `situacion_id` ASC) USING BTREE,
  INDEX `situacion_id`(`situacion_id` ASC) USING BTREE,
  CONSTRAINT `concepto_situaciones_ibfk_1` FOREIGN KEY (`concepto_id`) REFERENCES `concepto` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `concepto_situaciones_ibfk_2` FOREIGN KEY (`situacion_id`) REFERENCES `situaciones` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 232 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;


-- ----------------------------
-- Table structure for concepto_tipos_planilla
-- ----------------------------
DROP TABLE IF EXISTS `concepto_tipos_planilla`;
CREATE TABLE `concepto_tipos_planilla`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `concepto_id` int NOT NULL,
  `tipo_planilla_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `unique_concepto_tipo`(`concepto_id` ASC, `tipo_planilla_id` ASC) USING BTREE,
  INDEX `tipo_planilla_id`(`tipo_planilla_id` ASC) USING BTREE,
  CONSTRAINT `concepto_tipos_planilla_ibfk_1` FOREIGN KEY (`concepto_id`) REFERENCES `concepto` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `concepto_tipos_planilla_ibfk_2` FOREIGN KEY (`tipo_planilla_id`) REFERENCES `tipos_planilla` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 406 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;


-- ----------------------------
-- Table structure for conceptos_acumulados
-- ----------------------------
DROP TABLE IF EXISTS `conceptos_acumulados`;
CREATE TABLE `conceptos_acumulados`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `concepto_id` int NOT NULL,
  `tipo_acumulado_id` int NOT NULL,
  `factor_acumulacion` decimal(8, 4) NULL DEFAULT 1.0000,
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `unique_concepto_tipo`(`concepto_id` ASC, `tipo_acumulado_id` ASC) USING BTREE,
  INDEX `tipo_acumulado_id`(`tipo_acumulado_id` ASC) USING BTREE,
  CONSTRAINT `conceptos_acumulados_ibfk_1` FOREIGN KEY (`concepto_id`) REFERENCES `concepto` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `conceptos_acumulados_ibfk_2` FOREIGN KEY (`tipo_acumulado_id`) REFERENCES `tipos_acumulados` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 166 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;


-- ----------------------------
-- Table structure for creditors
-- ----------------------------
DROP TABLE IF EXISTS `creditors`;
CREATE TABLE `creditors`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `description` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `amount` double NOT NULL,
  `creditor_id` varchar(11) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `tipo` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT 'OTRO',
  `activo` tinyint(1) NULL DEFAULT 1,
  `observaciones` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `employee_id` varchar(11) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of creditors
-- ----------------------------
INSERT INTO `creditors` VALUES (1, 'BANCO GENERAL', 0, 'BANGE001', 'PERSONAL', 1, 'BANCO GENERAL', '2025-09-10 16:38:55', '2025-09-10 16:38:55', '');
INSERT INTO `creditors` VALUES (2, 'CAJA DE SEGURO SOCIAL', 0, 'SEG001', 'SEGURO', 1, '', '2025-10-04 14:04:31', '2025-10-04 14:04:31', '');
INSERT INTO `creditors` VALUES (3, 'DESCUENTO PLANILLA', 0, 'OTR001', 'OTRO', 1, 'DESCUENTOS', '2025-10-14 22:36:27', '2025-10-14 22:36:27', '');

-- ----------------------------
-- Table structure for creditors_table
-- ----------------------------
DROP TABLE IF EXISTS `creditors_table`;
CREATE TABLE `creditors_table`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `creditor_id` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `creditor_id`(`creditor_id` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of creditors_table
-- ----------------------------

-- ----------------------------
-- Table structure for deductions
-- ----------------------------
DROP TABLE IF EXISTS `deductions`;
CREATE TABLE `deductions`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `creditor_id` int NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `amount` decimal(10, 2) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fk_deductions_creditor`(`creditor_id` ASC) USING BTREE,
  CONSTRAINT `fk_deductions_creditor` FOREIGN KEY (`creditor_id`) REFERENCES `creditors` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of deductions
-- ----------------------------

-- ----------------------------
-- Table structure for empleados_acumulados_historicos
-- ----------------------------
DROP TABLE IF EXISTS `empleados_acumulados_historicos`;
CREATE TABLE `empleados_acumulados_historicos`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `empleado_id` int NOT NULL,
  `tipo_acumulado_id` int NOT NULL,
  `periodo_inicio` date NOT NULL,
  `periodo_fin` date NOT NULL,
  `total_acumulado` decimal(10, 2) NULL DEFAULT 0.00,
  `total_conceptos_incluidos` int NULL DEFAULT 0,
  `ultima_planilla_id` int NULL DEFAULT NULL,
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_empleado_tipo`(`empleado_id` ASC, `tipo_acumulado_id` ASC) USING BTREE,
  INDEX `idx_periodo`(`periodo_inicio` ASC, `periodo_fin` ASC) USING BTREE,
  INDEX `tipo_acumulado_id`(`tipo_acumulado_id` ASC) USING BTREE,
  INDEX `ultima_planilla_id`(`ultima_planilla_id` ASC) USING BTREE,
  CONSTRAINT `empleados_acumulados_historicos_ibfk_1` FOREIGN KEY (`empleado_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `empleados_acumulados_historicos_ibfk_2` FOREIGN KEY (`tipo_acumulado_id`) REFERENCES `tipos_acumulados` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `empleados_acumulados_historicos_ibfk_3` FOREIGN KEY (`ultima_planilla_id`) REFERENCES `planilla_cabecera` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of empleados_acumulados_historicos
-- ----------------------------

-- ----------------------------
-- Table structure for employee_payroll_salaries
-- ----------------------------
DROP TABLE IF EXISTS `employee_payroll_salaries`;
CREATE TABLE `employee_payroll_salaries`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL COMMENT 'FK a tabla employees',
  `tipo_planilla_id` int NOT NULL COMMENT 'FK a tabla tipos_planilla',
  `sueldo_base` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Salario base para este tipo de planilla',
  `gastos_representacion` decimal(10, 2) NULL DEFAULT 0.00 COMMENT 'Gastos de representación específicos',
  `fecha_inicio` date NOT NULL COMMENT 'Fecha de inicio de vigencia',
  `fecha_fin` date NULL DEFAULT NULL COMMENT 'Fecha de fin de vigencia (NULL = indefinido)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Indica si el registro está activo',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int NULL DEFAULT NULL COMMENT 'Usuario que creó el registro',
  `updated_by` int NULL DEFAULT NULL COMMENT 'Usuario que actualizó el registro',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'Observaciones sobre el salario',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `unique_employee_payroll_active`(`employee_id` ASC, `tipo_planilla_id` ASC, `is_active` ASC, `fecha_fin` ASC) USING BTREE,
  INDEX `idx_employee`(`employee_id` ASC) USING BTREE,
  INDEX `idx_tipo_planilla`(`tipo_planilla_id` ASC) USING BTREE,
  INDEX `idx_active`(`is_active` ASC) USING BTREE,
  INDEX `idx_vigencia`(`fecha_inicio` ASC, `fecha_fin` ASC) USING BTREE,
  INDEX `idx_employee_tipo`(`employee_id` ASC, `tipo_planilla_id` ASC) USING BTREE,
  CONSTRAINT `employee_payroll_salaries_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `employee_payroll_salaries_ibfk_2` FOREIGN KEY (`tipo_planilla_id`) REFERENCES `tipos_planilla` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 41 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Salarios de empleados diferenciados por tipo de planilla' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for employee_terminations
-- ----------------------------
DROP TABLE IF EXISTS `employee_terminations`;
CREATE TABLE `employee_terminations`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `termination_date` date NOT NULL,
  `termination_type` enum('DESPIDO_CON_CAUSA','DESPIDO_SIN_CAUSA','RENUNCIA','MUTUO_ACUERDO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `notice_period_days` int NULL DEFAULT 30,
  `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `liquidation_payroll_id` int NULL DEFAULT NULL,
  `years_worked` decimal(6, 2) NOT NULL DEFAULT 0.00,
  `months_worked_current_year` int NULL DEFAULT 0,
  `accumulated_vacations` decimal(8, 2) NULL DEFAULT 0.00,
  `status` enum('PENDIENTE','CALCULADA','PROCESADA','PAGADA','CANCELADA') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'PENDIENTE' COMMENT 'Estado de la liquidación: PENDIENTE->CALCULADA->PROCESADA->PAGADA, o CANCELADA en cualquier momento',
  `approved_by` int NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `cancelled_at` timestamp NULL DEFAULT NULL COMMENT 'Fecha y hora de cancelación',
  `cancelled_by` int NULL DEFAULT NULL COMMENT 'ID del usuario que canceló',
  `cancel_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT 'Motivo detallado de la cancelación',
  `previous_status` enum('PENDIENTE','CALCULADA','PROCESADA','PAGADA') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'Estado anterior antes de cancelar',
  `needs_recalculation` tinyint(1) NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fk_termination_employee`(`employee_id` ASC) USING BTREE,
  INDEX `fk_termination_payroll`(`liquidation_payroll_id` ASC) USING BTREE,
  INDEX `fk_termination_approved_by`(`approved_by` ASC) USING BTREE,
  INDEX `idx_terminations_date`(`termination_date` ASC) USING BTREE,
  INDEX `idx_terminations_status`(`status` ASC) USING BTREE,
  INDEX `idx_terminations_cancelled`(`status` ASC, `cancelled_at` ASC) USING BTREE,
  INDEX `idx_terminations_cancelled_by`(`cancelled_by` ASC, `cancelled_at` ASC) USING BTREE,
  INDEX `idx_terminations_recalc`(`needs_recalculation` ASC, `status` ASC) USING BTREE,
  CONSTRAINT `fk_termination_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `admin` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `fk_termination_cancelled_by` FOREIGN KEY (`cancelled_by`) REFERENCES `admin` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_termination_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'Gestión de terminaciones y liquidaciones con sistema de cancelación y auditoría completa' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for employees
-- ----------------------------
DROP TABLE IF EXISTS `employees`;
CREATE TABLE `employees`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `firstname` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `lastname` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `address` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `birthdate` date NOT NULL,
  `fecha_ingreso` date NULL DEFAULT NULL,
  `contact_info` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'email@empresa.com',
  `marca_asistencia` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Indica si empleado registra marcaciones (1=Sí por horas, 0=No sueldo fijo)',
  `permite_horas_extras` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Indica si el empleado es elegible para horas extras (1=S├¡, 0=No/Exento)',
  `gender` varchar(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `schedule_id` int NOT NULL,
  `photo` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `created_on` date NOT NULL,
  `id_partida` int NULL DEFAULT NULL,
  `id_cargo` int NULL DEFAULT NULL,
  `id_funcion` int NULL DEFAULT NULL,
  `position_id` int NULL DEFAULT NULL,
  `organigrama_path` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `document_id` varchar(31) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `clave_seguro_social` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `situacion_id` int NULL DEFAULT NULL,
  `tipo_planilla_id` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL COMMENT 'IDs de tipos de planilla separados por comas',
  `sueldo_individual` decimal(10, 2) NULL DEFAULT NULL,
  `cargo_id` int NULL DEFAULT NULL,
  `partida_id` int NULL DEFAULT NULL,
  `funcion_id` int NULL DEFAULT NULL,
  `organigrama_id` int NULL DEFAULT NULL COMMENT 'ID del elemento organizacional al que pertenece el empleado',
  `gastos_representacion` decimal(10, 2) NULL DEFAULT 0.00 COMMENT 'Gastos de representación asignados al empleado',
  `tipo_contrato` enum('INDEFINIDO','DEFINIDO','PROYECTO','TEMPORAL') CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT 'INDEFINIDO' COMMENT 'Tipo de contrato laboral del empleado',
  `fecha_inicio_contrato` date NULL DEFAULT NULL COMMENT 'Fecha de inicio del contrato actual',
  `fecha_vencimiento_contrato` date NULL DEFAULT NULL COMMENT 'Fecha de vencimiento del contrato (solo para contratos definidos)',
  `numero_contrato` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL COMMENT 'Número identificador del contrato',
  `forma_pago` enum('EFECTIVO','CHEQUE','ACH') CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT 'EFECTIVO' COMMENT 'Forma de pago del salario',
  `banco` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL COMMENT 'Nombre del banco (requerido para CHEQUE y ACH)',
  `numero_cuenta` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL COMMENT 'Número de cuenta bancaria (requerido para CHEQUE y ACH)',
  `tipo_cuenta` enum('AHORROS','CORRIENTE') CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL COMMENT 'Tipo de cuenta bancaria',
  `fecha_terminacion` date NULL DEFAULT NULL COMMENT 'Fecha de terminación del empleado (para vista rápida)',
  `motivo_terminacion` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL COMMENT 'Motivo básico de terminación (para vista rápida)',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `id_partida`(`id_partida` ASC) USING BTREE,
  INDEX `id_cargo`(`id_cargo` ASC) USING BTREE,
  INDEX `id_funcion`(`id_funcion` ASC) USING BTREE,
  INDEX `position_id`(`position_id` ASC) USING BTREE,
  INDEX `fk_employees_situacion`(`situacion_id` ASC) USING BTREE,
  INDEX `fk_employees_tipo_planilla`(`tipo_planilla_id` ASC) USING BTREE,
  INDEX `fk_employees_cargo`(`cargo_id` ASC) USING BTREE,
  INDEX `fk_employees_partida`(`partida_id` ASC) USING BTREE,
  INDEX `fk_employees_funcion`(`funcion_id` ASC) USING BTREE,
  INDEX `idx_employees_organigrama`(`organigrama_id` ASC) USING BTREE,
  INDEX `idx_employees_fecha_ingreso`(`fecha_ingreso` ASC) USING BTREE,
  INDEX `idx_employees_tipo_contrato`(`tipo_contrato` ASC) USING BTREE,
  INDEX `idx_employees_forma_pago`(`forma_pago` ASC) USING BTREE,
  INDEX `idx_employees_banco`(`banco` ASC) USING BTREE,
  INDEX `idx_employees_fecha_vencimiento`(`fecha_vencimiento_contrato` ASC) USING BTREE,
  INDEX `idx_employees_fecha_terminacion`(`fecha_terminacion` ASC) USING BTREE,
  INDEX `idx_employees_terminacion_composite`(`situacion_id` ASC, `fecha_terminacion` ASC) USING BTREE,
  INDEX `idx_marca_asistencia`(`marca_asistencia` ASC) USING BTREE,
  INDEX `idx_employees_overtime_eligible`(`permite_horas_extras` ASC) USING BTREE,
  CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`id_partida`) REFERENCES `partidas` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `employees_ibfk_2` FOREIGN KEY (`id_cargo`) REFERENCES `cargos` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `employees_ibfk_3` FOREIGN KEY (`id_funcion`) REFERENCES `funciones` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `employees_ibfk_4` FOREIGN KEY (`position_id`) REFERENCES `posiciones` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_employees_cargo` FOREIGN KEY (`cargo_id`) REFERENCES `cargos` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_employees_funcion` FOREIGN KEY (`funcion_id`) REFERENCES `funciones` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_employees_organigrama` FOREIGN KEY (`organigrama_id`) REFERENCES `organigrama` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_employees_partida` FOREIGN KEY (`partida_id`) REFERENCES `partidas` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_employees_situacion` FOREIGN KEY (`situacion_id`) REFERENCES `situaciones` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 18 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci COMMENT = 'Tabla de empleados con relación al organigrama empresarial' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for frecuencias
-- ----------------------------
DROP TABLE IF EXISTS `frecuencias`;
CREATE TABLE `frecuencias`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `activo` tinyint(1) NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `codigo`(`codigo` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 12 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of frecuencias
-- ----------------------------
INSERT INTO `frecuencias` VALUES (1, 'quincenal', 'Quincenal', 'Se aplica quincenalmente', 1, '2025-08-21 22:52:07', '2025-08-21 22:52:07');
INSERT INTO `frecuencias` VALUES (2, 'mensual', 'Mensual', 'Se aplica mensualmente', 1, '2025-08-21 22:52:07', '2025-08-21 22:52:07');
INSERT INTO `frecuencias` VALUES (3, 'semanal', 'Semanal', 'Se aplica semanalmente', 0, '2025-08-21 22:52:07', '2025-08-26 16:52:10');
INSERT INTO `frecuencias` VALUES (8, 'XIII', 'DECIMO', 'DECIMO TERCER MES', 1, '2025-09-08 09:55:09', '2025-09-08 09:55:20');
INSERT INTO `frecuencias` VALUES (9, 'LIQUIDACION', 'Liquidación', 'Frecuencia especial para planillas de liquidación', 1, '2025-09-24 12:04:02', '2025-09-24 12:04:02');
INSERT INTO `frecuencias` VALUES (11, 'Vacaciones', 'Vacaciones', 'Vacaciones', 1, '2025-11-14 14:53:52', '2025-11-14 14:53:52');

-- ----------------------------
-- Table structure for funciones
-- ----------------------------
DROP TABLE IF EXISTS `funciones`;
CREATE TABLE `funciones`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `unique_codigo`(`codigo` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of funciones
-- ----------------------------
INSERT INTO `funciones` VALUES (1, 'FUNC01', 'GERENTE DE VENTAS', 'VENTAS', 1, '2025-09-10 02:18:03', '2025-09-15 16:03:10');
INSERT INTO `funciones` VALUES (2, 'FUNC02', 'SOPORTE TECNICO', 'SOPORTE TECNICO', 1, '2025-09-11 18:43:22', '2025-09-11 18:52:31');
INSERT INTO `funciones` VALUES (3, 'FUNC03', 'ASISTENTE ADMINISTRATIVO', 'ADMINISTRACION Y CONTABILIDAD', 1, '2025-09-15 16:03:50', '2025-09-15 16:03:50');
INSERT INTO `funciones` VALUES (4, 'FUNC04', 'CEO', 'GERENTE GENERAL', 1, '2025-09-15 16:04:19', '2025-09-15 16:04:19');
INSERT INTO `funciones` VALUES (5, 'FUNC05', 'PROGRAMADOR SENIOR', 'PROGRAMADOR SENIOR', 1, '2025-09-25 22:48:19', '2025-09-25 22:48:51');

-- ----------------------------
-- Table structure for liquidation_calculations
-- ----------------------------
DROP TABLE IF EXISTS `liquidation_calculations`;
CREATE TABLE `liquidation_calculations`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `termination_id` int NOT NULL,
  `concept_id` int NULL DEFAULT NULL,
  `concept_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `concept_description` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `calculation_base` decimal(12, 2) NOT NULL DEFAULT 0.00,
  `years_worked` decimal(6, 2) NOT NULL DEFAULT 0.00,
  `weeks_entitled` decimal(6, 2) NOT NULL DEFAULT 0.00,
  `days_entitled` decimal(6, 2) NOT NULL DEFAULT 0.00,
  `calculated_amount` decimal(12, 2) NOT NULL DEFAULT 0.00,
  `formula_used` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fk_liquidation_termination`(`termination_id` ASC) USING BTREE,
  INDEX `idx_calculations_concept`(`concept_code` ASC) USING BTREE,
  INDEX `idx_liquidation_calc_concept`(`concept_id` ASC) USING BTREE,
  INDEX `idx_liquidation_calc_termination_concept`(`termination_id` ASC, `concept_id` ASC) USING BTREE,
  CONSTRAINT `fk_liquidation_calc_concept_id` FOREIGN KEY (`concept_id`) REFERENCES `concepto` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_liquidation_termination` FOREIGN KEY (`termination_id`) REFERENCES `employee_terminations` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 549 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for liquidation_config
-- ----------------------------
DROP TABLE IF EXISTS `liquidation_config`;
CREATE TABLE `liquidation_config`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `parameter_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `parameter_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `data_type` enum('STRING','NUMBER','BOOLEAN','JSON') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'STRING',
  `category` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'GENERAL',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `unique_parameter`(`parameter_name` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 15 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of liquidation_config
-- ----------------------------
INSERT INTO `liquidation_config` VALUES (1, 'PRIMA_ANTIGUEDAD_WEEKS', '1', 'Semanas de salario por año trabajado para prima de antigüedad', 'NUMBER', 'CALCULO', '2025-09-24 12:04:02', '2025-09-24 12:04:02');
INSERT INTO `liquidation_config` VALUES (2, 'INDEMNIZACION_WEEKS_FIRST_10', '3.4', 'Semanas de salario por año para los primeros 10 años de indemnización', 'NUMBER', 'CALCULO', '2025-09-24 12:04:02', '2025-09-24 12:04:02');
INSERT INTO `liquidation_config` VALUES (3, 'INDEMNIZACION_WEEKS_AFTER_10', '1', 'Semanas de salario por año después de 10 años para indemnización', 'NUMBER', 'CALCULO', '2025-09-24 12:04:02', '2025-09-24 12:04:02');
INSERT INTO `liquidation_config` VALUES (4, 'PREAVISO_DAYS', '30', 'Días de preaviso estándar', 'NUMBER', 'CALCULO', '2025-09-24 12:04:02', '2025-09-24 12:04:02');
INSERT INTO `liquidation_config` VALUES (5, 'VACATION_DAYS_PER_YEAR', '30', 'Días de vacaciones por año trabajado', 'NUMBER', 'CALCULO', '2025-09-24 12:04:02', '2025-09-24 12:04:02');
INSERT INTO `liquidation_config` VALUES (6, 'AUTO_CALCULATE', 'true', 'Calcular automáticamente al crear liquidación', 'BOOLEAN', 'SISTEMA', '2025-09-24 12:04:02', '2025-09-24 12:04:02');
INSERT INTO `liquidation_config` VALUES (7, 'REQUIRE_APPROVAL', 'true', 'Requiere aprobación antes de generar planilla', 'BOOLEAN', 'SISTEMA', '2025-09-24 12:04:02', '2025-09-24 12:04:02');

-- ----------------------------
-- Table structure for liquidation_history
-- ----------------------------
DROP TABLE IF EXISTS `liquidation_history`;
CREATE TABLE `liquidation_history`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `termination_id` int NOT NULL,
  `action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `user_id` int NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fk_history_termination`(`termination_id` ASC) USING BTREE,
  INDEX `fk_history_user`(`user_id` ASC) USING BTREE,
  CONSTRAINT `fk_history_termination` FOREIGN KEY (`termination_id`) REFERENCES `employee_terminations` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_history_user` FOREIGN KEY (`user_id`) REFERENCES `admin` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 70 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for menu_items
-- ----------------------------
DROP TABLE IF EXISTS `menu_items`;
CREATE TABLE `menu_items`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 18 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of menu_items
-- ----------------------------
INSERT INTO `menu_items` VALUES (1, 'Dashboard', 'home.php');
INSERT INTO `menu_items` VALUES (2, 'Datos de empresa', 'datos_empresa.php');
INSERT INTO `menu_items` VALUES (3, 'Posiciones', 'posiciones.php');
INSERT INTO `menu_items` VALUES (4, 'Partidas', 'partidas.php');
INSERT INTO `menu_items` VALUES (5, 'Organigrama', 'organigrama.php');
INSERT INTO `menu_items` VALUES (6, 'Cargos', 'cargos.php');
INSERT INTO `menu_items` VALUES (7, 'Funciones', 'funciones.php');
INSERT INTO `menu_items` VALUES (8, 'Colaboradores - Listado', 'employee.php');
INSERT INTO `menu_items` VALUES (9, 'Colaboradores - Horas Extras', 'overtime.php');
INSERT INTO `menu_items` VALUES (10, 'Colaboradores - Horarios', 'schedule_employee.php');
INSERT INTO `menu_items` VALUES (11, 'Asistencia', 'attendance.php');
INSERT INTO `menu_items` VALUES (12, 'Acreedores', 'deduction.php');
INSERT INTO `menu_items` VALUES (13, 'Planilla', 'planilla_cabecera.php');
INSERT INTO `menu_items` VALUES (14, 'Conceptos - Asignaciones', 'asignaciones.php');
INSERT INTO `menu_items` VALUES (15, 'Conceptos - Deducciones', 'deducciones.php');
INSERT INTO `menu_items` VALUES (16, 'Comprobantes', 'payroll.php');
INSERT INTO `menu_items` VALUES (17, 'Roles', 'roles');

-- ----------------------------
-- Table structure for organigrama
-- ----------------------------
DROP TABLE IF EXISTS `organigrama`;
CREATE TABLE `organigrama`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nombre del elemento organizacional',
  `id_padre` int NULL DEFAULT NULL COMMENT 'ID del elemento padre (NULL para raíz)',
  `path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Ruta jerárquica completa',
  `nivel` int NULL DEFAULT 0 COMMENT 'Nivel en la jerarquía (0 = raíz)',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_organigrama_padre`(`id_padre` ASC) USING BTREE,
  INDEX `idx_organigrama_path`(`path`(191) ASC) USING BTREE,
  INDEX `idx_organigrama_nivel`(`nivel` ASC) USING BTREE,
  CONSTRAINT `fk_organigrama_padre` FOREIGN KEY (`id_padre`) REFERENCES `organigrama` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 9 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Estructura organizacional de la empresa' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of organigrama
-- ----------------------------
INSERT INTO `organigrama` VALUES (1, 'DIRECCIÓN GENERAL', NULL, '/direcci-n-general/', 0, '2025-09-16 19:59:46', '2025-09-17 10:53:05');
INSERT INTO `organigrama` VALUES (2, 'SOPORTE TÉCNICO', 1, '/direcci-n-general/soporte-t-cnico/', 1, '2025-09-16 19:59:46', '2025-09-17 10:53:15');
INSERT INTO `organigrama` VALUES (6, 'VENTAS', 1, '/direcci-n-general/ventas/', 0, '2025-09-17 10:53:43', '2025-09-17 10:53:43');
INSERT INTO `organigrama` VALUES (7, 'DESARROLLO', 1, '/direcci-n-general/desarrollo/', 0, '2025-09-17 10:53:53', '2025-09-17 10:53:53');
INSERT INTO `organigrama` VALUES (8, 'ADMINISTRACIÓN', 1, '/direcci-n-general/administraci-n/', 0, '2025-09-17 10:54:18', '2025-09-17 10:54:18');

-- ----------------------------
-- Table structure for overtime
-- ----------------------------
DROP TABLE IF EXISTS `overtime`;
CREATE TABLE `overtime`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `hours` double NOT NULL,
  `rate` double NOT NULL,
  `date_overtime` date NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of overtime
-- ----------------------------

-- ----------------------------
-- Table structure for partidas
-- ----------------------------
DROP TABLE IF EXISTS `partidas`;
CREATE TABLE `partidas`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `partida` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `unique_codigo`(`codigo` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 26 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of partidas
-- ----------------------------
INSERT INTO `partidas` VALUES (1, '6.10.10.004.', 'Xii mes', 'Xii mes', 1, '2025-09-10 02:17:46', '2025-09-25 22:25:16', NULL);
INSERT INTO `partidas` VALUES (2, '6.10.10.001.', 'Sueldos', 'Sueldos', 1, '2025-09-11 18:46:20', '2025-09-25 22:29:00', NULL);
INSERT INTO `partidas` VALUES (3, '6.10.10.002.', 'Horas extras', 'Horas extras', 1, '2025-09-25 22:23:22', '2025-09-25 22:23:22', NULL);
INSERT INTO `partidas` VALUES (4, '6.10.10.003.', 'Vacaciones', 'Vacaciones', 1, '2025-09-25 22:24:29', '2025-09-25 22:24:29', NULL);
INSERT INTO `partidas` VALUES (5, '6.10.10.007.', 'Gasto de representacion', 'Gasto de representación', 1, '2025-09-25 22:28:29', '2025-09-25 22:28:29', NULL);
INSERT INTO `partidas` VALUES (6, '6.10.10.008.', 'Xiii gasto de representacion', 'Xiii gasto de representación', 1, '2025-09-25 22:30:05', '2025-09-25 22:30:05', NULL);
INSERT INTO `partidas` VALUES (7, '6.10.10.009.', 'Viatico', 'Viatico', 1, '2025-09-25 22:30:48', '2025-09-25 22:30:48', NULL);
INSERT INTO `partidas` VALUES (8, '6.10.10.010.', 'Bonificacion', 'Bonificación', 1, '2025-09-25 22:32:00', '2025-09-25 22:32:00', NULL);
INSERT INTO `partidas` VALUES (9, '6.10.10.012.', 'Prima de antiguedad', 'Prima de antigüedad', 1, '2025-09-25 22:32:44', '2025-09-25 22:32:44', NULL);
INSERT INTO `partidas` VALUES (10, '6.10.10.013.', 'Indemnizacion', 'Indemnización', 1, '2025-09-25 22:33:32', '2025-09-25 22:33:32', NULL);
INSERT INTO `partidas` VALUES (11, '6.10.10.019.', 'Seguro social patronal', 'Seguro social patronal', 1, '2025-09-25 22:35:15', '2025-09-25 22:35:15', NULL);
INSERT INTO `partidas` VALUES (12, '6.10.10.020.', 'Seguro educativo patronal', 'Seguro educativo patronal', 1, '2025-09-25 22:36:34', '2025-09-25 22:36:34', NULL);
INSERT INTO `partidas` VALUES (13, '6.10.10.021.', 'Seguro riesgo profesional', 'Seguro riesgo profesional', 1, '2025-09-25 22:37:19', '2025-09-25 22:37:19', NULL);
INSERT INTO `partidas` VALUES (14, '6.10.10.024.', 'Recargos seguro social', 'Recargos seguro social', 1, '2025-09-25 22:38:11', '2025-09-25 22:38:11', NULL);
INSERT INTO `partidas` VALUES (15, '2.10.12.001.', 'Seguro social por pagar', 'Seguro social por pagar', 1, '2025-09-25 22:39:46', '2025-09-25 22:39:46', NULL);
INSERT INTO `partidas` VALUES (16, '2.10.12.002.', 'Salarios por pagar', 'Salarios por pagar', 1, '2025-09-25 22:40:41', '2025-09-25 22:40:41', NULL);
INSERT INTO `partidas` VALUES (17, '2.10.12.005.', 'Imp. sobre la renta por pagar', 'Imp. sobre la renta por pagar', 1, '2025-09-25 22:41:28', '2025-09-25 22:41:28', NULL);
INSERT INTO `partidas` VALUES (18, '2.10.12.010.', 'Seguro educativo por pagar', 'Seguro educativo por pagar', 1, '2025-09-25 22:42:08', '2025-09-25 22:42:08', NULL);
INSERT INTO `partidas` VALUES (19, '2.10.12.011.', 'Seguro riesgo profesional por pagar', 'Seguro riesgo profesional por pagar', 1, '2025-09-25 22:42:48', '2025-09-25 22:42:48', NULL);
INSERT INTO `partidas` VALUES (20, '2.10.12.012.', 'Decimo tercer mes por pagar', 'Decimo tercer mes por pagar', 1, '2025-09-25 22:43:55', '2025-09-25 22:43:55', NULL);
INSERT INTO `partidas` VALUES (21, '6.10.99.', 'IMPUESTO SOBRE LA RENTA', 'IMPUESTO SOBRE LA RENTA', 1, '2025-09-25 22:46:55', '2025-09-25 22:46:55', NULL);
INSERT INTO `partidas` VALUES (22, '6.10.15.002.', 'Honorarios de contabilidad', 'Honorarios de contabilidad', 1, '2025-10-02 15:07:40', '2025-10-02 15:07:40', NULL);
INSERT INTO `partidas` VALUES (23, '6.10.15.004.', 'Honorarios por soporte tecnico', 'Honorarios por soporte tecnico', 1, '2025-10-02 15:11:43', '2025-10-02 15:11:43', NULL);
INSERT INTO `partidas` VALUES (24, '6.10.15.011.', 'Honorarios por desarrollo', 'Honorarios por desarrollo', 1, '2025-10-02 15:14:43', '2025-10-02 15:14:43', NULL);
INSERT INTO `partidas` VALUES (25, '6.10.15.012.', 'Honorarios por administracion', 'Honorarios por administracion', 1, '2025-10-02 15:15:02', '2025-10-02 15:15:02', NULL);

-- ----------------------------
-- Table structure for payroll_attendance_details
-- ----------------------------
DROP TABLE IF EXISTS `payroll_attendance_details`;
CREATE TABLE `payroll_attendance_details`  (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'ID ├║nico del detalle',
  `summary_id` int NOT NULL COMMENT 'ID del resumen en payroll_attendance_summary',
  `attendance_id` int NULL DEFAULT NULL COMMENT 'ID de la asistencia en tabla attendance',
  `calculation_id` int NULL DEFAULT NULL COMMENT 'ID del c├ílculo en attendance_calculations',
  `attendance_date` date NOT NULL COMMENT 'Fecha de la asistencia',
  `day_of_week` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'D├¡a de la semana',
  `day_type` enum('LABORAL','FERIADO','DUELO_NACIONAL','FIN_SEMANA','ESPECIAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'LABORAL' COMMENT 'Tipo de d├¡a',
  `time_in` time NULL DEFAULT NULL COMMENT 'Hora de entrada',
  `time_out` time NULL DEFAULT NULL COMMENT 'Hora de salida',
  `scheduled_in` time NULL DEFAULT NULL COMMENT 'Hora entrada programada',
  `scheduled_out` time NULL DEFAULT NULL COMMENT 'Hora salida programada',
  `hours_worked` decimal(5, 2) NULL DEFAULT 0.00 COMMENT 'Horas trabajadas en el d├¡a',
  `overtime_hours` decimal(5, 2) NULL DEFAULT 0.00 COMMENT 'Horas extras del d├¡a',
  `night_hours` decimal(5, 2) NULL DEFAULT 0.00 COMMENT 'Horas nocturnas del d├¡a',
  `tardiness_minutes` int NULL DEFAULT 0 COMMENT 'Minutos de tardanza',
  `early_departure_minutes` int NULL DEFAULT 0 COMMENT 'Minutos salida anticipada',
  `lunch_time_minutes` int NULL DEFAULT 0 COMMENT 'Minutos de almuerzo',
  `status` enum('PRESENT','ABSENT','JUSTIFIED','HOLIDAY','WEEKEND','PARTIAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'PRESENT' COMMENT 'Estado de asistencia del d├¡a',
  `is_perfect_attendance` tinyint(1) NULL DEFAULT 0 COMMENT 'Asistencia perfecta ese d├¡a',
  `concepts_applied` json NULL COMMENT 'Conceptos aplicados este d├¡a en formato JSON',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'Notas adicionales del d├¡a',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_summary`(`summary_id` ASC) USING BTREE,
  INDEX `idx_attendance`(`attendance_id` ASC) USING BTREE,
  INDEX `idx_calculation`(`calculation_id` ASC) USING BTREE,
  INDEX `idx_date`(`attendance_date` ASC) USING BTREE,
  INDEX `idx_status`(`status` ASC) USING BTREE,
  CONSTRAINT `fk_detail_attendance` FOREIGN KEY (`attendance_id`) REFERENCES `attendance` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `fk_detail_calculation` FOREIGN KEY (`calculation_id`) REFERENCES `attendance_calculations` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `fk_detail_summary` FOREIGN KEY (`summary_id`) REFERENCES `payroll_attendance_summary` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Detalle granular d├¡a por d├¡a de asistencias incluidas en planilla' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of payroll_attendance_details
-- ----------------------------

-- ----------------------------
-- Table structure for payroll_attendance_summary
-- ----------------------------
DROP TABLE IF EXISTS `payroll_attendance_summary`;
CREATE TABLE `payroll_attendance_summary`  (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'ID ├║nico del resumen',
  `planilla_cabecera_id` int NOT NULL COMMENT 'ID de la planilla',
  `employee_id` int NOT NULL COMMENT 'ID del empleado',
  `period_start` date NOT NULL COMMENT 'Fecha inicio del per├¡odo',
  `period_end` date NOT NULL COMMENT 'Fecha fin del per├¡odo',
  `total_hours_worked` decimal(10, 2) NULL DEFAULT 0.00 COMMENT 'Total horas trabajadas',
  `regular_hours` decimal(10, 2) NULL DEFAULT 0.00 COMMENT 'Horas regulares (hasta 8h/d├¡a)',
  `overtime_hours_25` decimal(10, 2) NULL DEFAULT 0.00 COMMENT 'Horas extras con recargo 25%',
  `overtime_hours_50` decimal(10, 2) NULL DEFAULT 0.00 COMMENT 'Horas extras con recargo 50%',
  `night_hours` decimal(10, 2) NULL DEFAULT 0.00 COMMENT 'Horas nocturnas (6PM-6AM)',
  `holiday_hours` decimal(10, 2) NULL DEFAULT 0.00 COMMENT 'Horas trabajadas en feriados',
  `sunday_hours` decimal(10, 2) NULL DEFAULT 0.00 COMMENT 'Horas trabajadas en domingos',
  `total_days_worked` int NULL DEFAULT 0 COMMENT 'Total d├¡as trabajados',
  `total_absences` int NULL DEFAULT 0 COMMENT 'Total ausencias',
  `justified_absences` int NULL DEFAULT 0 COMMENT 'Ausencias justificadas',
  `unjustified_absences` int NULL DEFAULT 0 COMMENT 'Ausencias injustificadas',
  `pending_absences` int NULL DEFAULT 0 COMMENT 'Ausencias pendientes de justificar',
  `perfect_attendance_days` int NULL DEFAULT 0 COMMENT 'D├¡as con asistencia perfecta',
  `total_tardiness_minutes` int NULL DEFAULT 0 COMMENT 'Total minutos de tardanza',
  `tardiness_count` int NULL DEFAULT 0 COMMENT 'Cantidad de tardanzas',
  `early_departures_count` int NULL DEFAULT 0 COMMENT 'Cantidad de salidas anticipadas',
  `punctuality_score` decimal(5, 2) NULL DEFAULT 100.00 COMMENT 'Score de puntualidad (0-100)',
  `total_lunch_time_minutes` int NULL DEFAULT 0 COMMENT 'Total minutos de almuerzo',
  `lunch_violations` int NULL DEFAULT 0 COMMENT 'Violaciones tiempo almuerzo (<30min)',
  `total_regular_pay` decimal(10, 2) NULL DEFAULT 0.00 COMMENT 'Pago por horas regulares',
  `total_overtime_pay` decimal(10, 2) NULL DEFAULT 0.00 COMMENT 'Pago por horas extras',
  `total_night_pay` decimal(10, 2) NULL DEFAULT 0.00 COMMENT 'Pago por horas nocturnas',
  `total_holiday_pay` decimal(10, 2) NULL DEFAULT 0.00 COMMENT 'Pago por horas feriados',
  `total_deductions` decimal(10, 2) NULL DEFAULT 0.00 COMMENT 'Total deducciones (tardanzas, ausencias)',
  `total_bonuses` decimal(10, 2) NULL DEFAULT 0.00 COMMENT 'Total bonificaciones (puntualidad, asistencia perfecta)',
  `net_attendance_pay` decimal(10, 2) NULL DEFAULT 0.00 COMMENT 'Total neto de asistencias',
  `legal_compliant` tinyint(1) NULL DEFAULT 1 COMMENT 'Si cumple con normativa legal',
  `legal_violations_count` int NULL DEFAULT 0 COMMENT 'Cantidad de violaciones legales',
  `legal_warnings_count` int NULL DEFAULT 0 COMMENT 'Cantidad de advertencias legales',
  `legal_risk_level` enum('NINGUNO','BAJO','MEDIO','ALTO','CR├ìTICO') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'NINGUNO' COMMENT 'Nivel de riesgo legal',
  `legal_compliance_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'Notas sobre cumplimiento legal',
  `alerts_generated` int NULL DEFAULT 0 COMMENT 'Cantidad de alertas generadas',
  `critical_alerts` int NULL DEFAULT 0 COMMENT 'Cantidad de alertas cr├¡ticas',
  `pending_alerts` int NULL DEFAULT 0 COMMENT 'Cantidad de alertas pendientes',
  `processing_status` enum('PENDING','IN_PROGRESS','COMPLETED','ERROR') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'PENDING' COMMENT 'Estado del procesamiento',
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'Mensaje de error si fall├│',
  `metadata` json NULL COMMENT 'Datos adicionales en formato JSON',
  `processed_by` int NULL DEFAULT NULL COMMENT 'Usuario que proces├│',
  `processed_at` timestamp NULL DEFAULT NULL COMMENT 'Fecha de procesamiento',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `idx_unique_planilla_employee`(`planilla_cabecera_id` ASC, `employee_id` ASC) USING BTREE,
  INDEX `idx_employee`(`employee_id` ASC) USING BTREE,
  INDEX `idx_period`(`period_start` ASC, `period_end` ASC) USING BTREE,
  INDEX `idx_status`(`processing_status` ASC) USING BTREE,
  INDEX `idx_legal_risk`(`legal_risk_level` ASC) USING BTREE,
  INDEX `idx_processed_at`(`processed_at` ASC) USING BTREE,
  CONSTRAINT `fk_summary_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_summary_planilla` FOREIGN KEY (`planilla_cabecera_id`) REFERENCES `planilla_cabecera` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Resumen consolidado de asistencias por planilla y empleado' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of payroll_attendance_summary
-- ----------------------------

-- ----------------------------
-- Table structure for planilla_auditoria
-- ----------------------------
DROP TABLE IF EXISTS `planilla_auditoria`;
CREATE TABLE `planilla_auditoria`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `planilla_id` int NOT NULL,
  `estado_anterior` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado_nuevo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_cambio` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `motivo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `acumulados_afectados` int NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_planilla_fecha`(`planilla_id` ASC, `fecha_cambio` ASC) USING BTREE,
  CONSTRAINT `planilla_auditoria_ibfk_1` FOREIGN KEY (`planilla_id`) REFERENCES `planilla_cabecera` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 160 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for planilla_cabecera
-- ----------------------------
DROP TABLE IF EXISTS `planilla_cabecera`;
CREATE TABLE `planilla_cabecera`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo_planilla_id` int NULL DEFAULT NULL,
  `frecuencia_id` int NULL DEFAULT NULL,
  `fecha` date NOT NULL,
  `estado` enum('PENDIENTE','PROCESADA','CERRADA') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'PENDIENTE',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_desde` date NULL DEFAULT NULL,
  `fecha_hasta` date NULL DEFAULT NULL,
  `fecha_reapertura` timestamp NULL DEFAULT NULL,
  `usuario_reapertura` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `motivo_reapertura` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `fecha_cierre` timestamp NULL DEFAULT NULL COMMENT 'Fecha de cierre de la planilla',
  `usuario_cierre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'Usuario que cerró la planilla',
  `acumulados_generados` tinyint(1) NULL DEFAULT 0 COMMENT 'Flag: acumulados fueron generados (1=sí, 0=no)',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_fecha`(`fecha` ASC) USING BTREE,
  INDEX `fk_planilla_tipo`(`tipo_planilla_id` ASC) USING BTREE,
  INDEX `fk_planilla_frecuencia`(`frecuencia_id` ASC) USING BTREE,
  CONSTRAINT `fk_planilla_tipo` FOREIGN KEY (`tipo_planilla_id`) REFERENCES `tipos_planilla` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 100 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for planilla_detalle
-- ----------------------------
DROP TABLE IF EXISTS `planilla_detalle`;
CREATE TABLE `planilla_detalle`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `planilla_cabecera_id` int NOT NULL,
  `employee_id` int NOT NULL,
  `concepto_id` int NOT NULL,
  `monto` decimal(10, 2) NOT NULL,
  `tipo` enum('A','D','P') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `organigrama_id` int NULL DEFAULT NULL,
  `organigrama_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `position_id` int NULL DEFAULT NULL,
  `schedule_id` int NULL DEFAULT NULL,
  `firstname` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `lastname` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_transaccion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `valores_editados_manual` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `conceptos_editados_manual` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `cargo_id` int NULL DEFAULT NULL,
  `funcion_id` int NULL DEFAULT NULL,
  `partida_id` int NULL DEFAULT NULL,
  `referencia_valor` decimal(10, 2) NULL DEFAULT NULL COMMENT 'Valor de referencia para cálculos según unidad del concepto (días, horas, monto, %)',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_planilla_cabecera_id`(`planilla_cabecera_id` ASC) USING BTREE,
  INDEX `idx_employee_id`(`employee_id` ASC) USING BTREE,
  INDEX `idx_concepto_id`(`concepto_id` ASC) USING BTREE,
  INDEX `idx_planilla_detalle_referencia`(`referencia_valor` ASC) USING BTREE,
  INDEX `idx_planilla_detalle_organigrama`(`organigrama_id` ASC) USING BTREE,
  CONSTRAINT `fk_planilla_detalle_organigrama` FOREIGN KEY (`organigrama_id`) REFERENCES `organigrama` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 2213 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'Tabla de detalles de planilla con valores de referencia para cálculos' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for planillas_acumulados_consolidados
-- ----------------------------
DROP TABLE IF EXISTS `planillas_acumulados_consolidados`;
CREATE TABLE `planillas_acumulados_consolidados`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `planilla_id` int NOT NULL,
  `tipo_acumulado_id` int NOT NULL,
  `empleado_id` int NOT NULL,
  `concepto_id` int NOT NULL,
  `monto_concepto` decimal(10, 2) NOT NULL,
  `factor_acumulacion` decimal(8, 4) NULL DEFAULT 1.0000,
  `monto_acumulado` decimal(10, 2) NOT NULL,
  `periodo_inicio` date NOT NULL,
  `periodo_fin` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_planilla`(`planilla_id` ASC) USING BTREE,
  INDEX `idx_empleado_tipo`(`empleado_id` ASC, `tipo_acumulado_id` ASC) USING BTREE,
  INDEX `tipo_acumulado_id`(`tipo_acumulado_id` ASC) USING BTREE,
  INDEX `concepto_id`(`concepto_id` ASC) USING BTREE,
  CONSTRAINT `planillas_acumulados_consolidados_ibfk_1` FOREIGN KEY (`planilla_id`) REFERENCES `planilla_cabecera` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `planillas_acumulados_consolidados_ibfk_2` FOREIGN KEY (`tipo_acumulado_id`) REFERENCES `tipos_acumulados` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `planillas_acumulados_consolidados_ibfk_3` FOREIGN KEY (`empleado_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `planillas_acumulados_consolidados_ibfk_4` FOREIGN KEY (`concepto_id`) REFERENCES `concepto` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of planillas_acumulados_consolidados
-- ----------------------------

-- ----------------------------
-- Table structure for posiciones
-- ----------------------------
DROP TABLE IF EXISTS `posiciones`;
CREATE TABLE `posiciones`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `id_cargo` int NULL DEFAULT NULL,
  `id_funcion` int NULL DEFAULT NULL,
  `id_partida` int NULL DEFAULT NULL,
  `sueldo` decimal(10, 2) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of posiciones
-- ----------------------------
INSERT INTO `posiciones` VALUES (1, '001', 1, 1, 2, 600.00);

-- ----------------------------
-- Table structure for position
-- ----------------------------
DROP TABLE IF EXISTS `position`;
CREATE TABLE `position`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `description` varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `rate` double NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of position
-- ----------------------------
INSERT INTO `position` VALUES (1, 'Programmer', 30000);
INSERT INTO `position` VALUES (2, 'Writer', 25000);

-- ----------------------------
-- Table structure for role_permissions
-- ----------------------------
DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE `role_permissions`  (
  `role_id` int NOT NULL,
  `menu_id` int NOT NULL,
  `read_perm` tinyint(1) NULL DEFAULT 0,
  `write_perm` tinyint(1) NULL DEFAULT 0,
  `delete_perm` tinyint(1) NULL DEFAULT 0,
  PRIMARY KEY (`role_id`, `menu_id`) USING BTREE,
  INDEX `menu_id`(`menu_id` ASC) USING BTREE,
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`menu_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of role_permissions
-- ----------------------------
INSERT INTO `role_permissions` VALUES (1, 1, 1, 1, 1);
INSERT INTO `role_permissions` VALUES (1, 2, 1, 1, 1);
INSERT INTO `role_permissions` VALUES (1, 3, 1, 1, 1);
INSERT INTO `role_permissions` VALUES (1, 4, 1, 1, 1);
INSERT INTO `role_permissions` VALUES (1, 5, 1, 1, 1);
INSERT INTO `role_permissions` VALUES (1, 6, 1, 1, 1);
INSERT INTO `role_permissions` VALUES (1, 7, 1, 1, 1);
INSERT INTO `role_permissions` VALUES (1, 8, 1, 1, 1);
INSERT INTO `role_permissions` VALUES (1, 9, 1, 1, 1);
INSERT INTO `role_permissions` VALUES (1, 10, 1, 1, 1);
INSERT INTO `role_permissions` VALUES (1, 11, 1, 1, 1);
INSERT INTO `role_permissions` VALUES (1, 12, 1, 1, 1);
INSERT INTO `role_permissions` VALUES (1, 13, 1, 1, 1);
INSERT INTO `role_permissions` VALUES (1, 14, 1, 1, 1);
INSERT INTO `role_permissions` VALUES (1, 15, 1, 1, 1);
INSERT INTO `role_permissions` VALUES (1, 16, 1, 1, 1);
INSERT INTO `role_permissions` VALUES (1, 17, 1, 1, 1);
INSERT INTO `role_permissions` VALUES (2, 2, 1, 1, 1);
INSERT INTO `role_permissions` VALUES (2, 3, 1, 1, 1);
INSERT INTO `role_permissions` VALUES (2, 4, 1, 1, 1);
INSERT INTO `role_permissions` VALUES (2, 5, 1, 1, 1);
INSERT INTO `role_permissions` VALUES (2, 6, 1, 1, 1);
INSERT INTO `role_permissions` VALUES (2, 7, 1, 1, 1);
INSERT INTO `role_permissions` VALUES (2, 8, 1, 1, 1);
INSERT INTO `role_permissions` VALUES (2, 9, 1, 1, 1);
INSERT INTO `role_permissions` VALUES (2, 10, 1, 1, 1);
INSERT INTO `role_permissions` VALUES (2, 11, 1, 1, 1);
INSERT INTO `role_permissions` VALUES (2, 12, 1, 1, 1);
INSERT INTO `role_permissions` VALUES (2, 13, 1, 1, 1);
INSERT INTO `role_permissions` VALUES (2, 14, 1, 1, 1);
INSERT INTO `role_permissions` VALUES (2, 15, 1, 1, 1);
INSERT INTO `role_permissions` VALUES (2, 16, 1, 1, 1);
INSERT INTO `role_permissions` VALUES (3, 1, 1, 0, 0);
INSERT INTO `role_permissions` VALUES (3, 8, 1, 0, 0);
INSERT INTO `role_permissions` VALUES (3, 13, 1, 0, 0);
INSERT INTO `role_permissions` VALUES (3, 14, 1, 0, 0);

-- ----------------------------
-- Table structure for roles
-- ----------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `status` tinyint(1) NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of roles
-- ----------------------------
INSERT INTO `roles` VALUES (1, 'ADMIN', 'Administrador', 1, '2025-07-14 16:18:03');
INSERT INTO `roles` VALUES (2, 'USUARIO', 'USER', 1, '2025-07-15 19:40:03');
INSERT INTO `roles` VALUES (3, 'Solo Lectura', 'Usuario con permisos de solo lectura', 1, '2025-08-28 22:21:38');

-- ----------------------------
-- Table structure for route_permissions
-- ----------------------------
DROP TABLE IF EXISTS `route_permissions`;
CREATE TABLE `route_permissions`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `route` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `menu_id` int NOT NULL,
  `permission_type` enum('read','write','delete') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'read',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `route`(`route` ASC) USING BTREE,
  INDEX `menu_id`(`menu_id` ASC) USING BTREE,
  CONSTRAINT `route_permissions_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 32 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of route_permissions
-- ----------------------------
INSERT INTO `route_permissions` VALUES (1, 'panel/dashboard', 1, 'read', 'Ver dashboard', '2025-08-28 21:48:43');
INSERT INTO `route_permissions` VALUES (2, 'panel/employees', 8, 'read', 'Listar empleados', '2025-08-28 21:48:43');
INSERT INTO `route_permissions` VALUES (3, 'panel/employees/create', 8, 'write', 'Crear empleado', '2025-08-28 21:48:43');
INSERT INTO `route_permissions` VALUES (4, 'panel/employees/*/edit', 8, 'write', 'Editar empleado', '2025-08-28 21:48:43');
INSERT INTO `route_permissions` VALUES (5, 'panel/employees/*/delete', 8, 'delete', 'Eliminar empleado', '2025-08-28 21:48:43');
INSERT INTO `route_permissions` VALUES (6, 'panel/positions', 3, 'read', 'Listar posiciones', '2025-08-28 21:48:43');
INSERT INTO `route_permissions` VALUES (7, 'panel/positions/create', 3, 'write', 'Crear posición', '2025-08-28 21:48:43');
INSERT INTO `route_permissions` VALUES (8, 'panel/positions/*/edit', 3, 'write', 'Editar posición', '2025-08-28 21:48:43');
INSERT INTO `route_permissions` VALUES (9, 'panel/positions/*/delete', 3, 'delete', 'Eliminar posición', '2025-08-28 21:48:43');
INSERT INTO `route_permissions` VALUES (10, 'panel/concepts', 14, 'read', 'Listar conceptos', '2025-08-28 21:48:43');
INSERT INTO `route_permissions` VALUES (11, 'panel/concepts/create', 14, 'write', 'Crear concepto', '2025-08-28 21:48:43');
INSERT INTO `route_permissions` VALUES (12, 'panel/concepts/*/edit', 14, 'write', 'Editar concepto', '2025-08-28 21:48:43');
INSERT INTO `route_permissions` VALUES (13, 'panel/concepts/*/delete', 14, 'delete', 'Eliminar concepto', '2025-08-28 21:48:43');
INSERT INTO `route_permissions` VALUES (14, 'panel/payrolls', 13, 'read', 'Listar planillas', '2025-08-28 21:48:43');
INSERT INTO `route_permissions` VALUES (15, 'panel/payrolls/create', 13, 'write', 'Crear planilla', '2025-08-28 21:48:43');
INSERT INTO `route_permissions` VALUES (16, 'panel/payrolls/*/edit', 13, 'write', 'Editar planilla', '2025-08-28 21:48:43');
INSERT INTO `route_permissions` VALUES (17, 'panel/payrolls/*/delete', 13, 'delete', 'Eliminar planilla', '2025-08-28 21:48:43');
INSERT INTO `route_permissions` VALUES (18, 'panel/users', 16, 'read', 'Listar usuarios', '2025-08-28 21:48:43');
INSERT INTO `route_permissions` VALUES (19, 'panel/users/create', 16, 'write', 'Crear usuario', '2025-08-28 21:48:43');
INSERT INTO `route_permissions` VALUES (20, 'panel/users/*/edit', 16, 'write', 'Editar usuario', '2025-08-28 21:48:43');
INSERT INTO `route_permissions` VALUES (21, 'panel/users/*/delete', 16, 'delete', 'Eliminar usuario', '2025-08-28 21:48:43');
INSERT INTO `route_permissions` VALUES (22, 'panel/roles', 17, 'read', 'Listar roles', '2025-08-28 21:48:43');
INSERT INTO `route_permissions` VALUES (23, 'panel/roles/create', 17, 'write', 'Crear rol', '2025-08-28 21:48:43');
INSERT INTO `route_permissions` VALUES (24, 'panel/roles/*/edit', 17, 'write', 'Editar rol', '2025-08-28 21:48:43');
INSERT INTO `route_permissions` VALUES (25, 'panel/roles/*/delete', 17, 'delete', 'Eliminar rol', '2025-08-28 21:48:43');
INSERT INTO `route_permissions` VALUES (26, 'panel/creditors', 12, 'read', 'Listar acreedores', '2025-08-28 21:48:43');
INSERT INTO `route_permissions` VALUES (27, 'panel/creditors/create', 12, 'write', 'Crear acreedor', '2025-08-28 21:48:43');
INSERT INTO `route_permissions` VALUES (28, 'panel/creditors/*/edit', 12, 'write', 'Editar acreedor', '2025-08-28 21:48:43');
INSERT INTO `route_permissions` VALUES (29, 'panel/creditors/*/delete', 12, 'delete', 'Eliminar acreedor', '2025-08-28 21:48:43');
INSERT INTO `route_permissions` VALUES (30, 'panel/company', 2, 'read', 'Ver configuración empresa', '2025-08-28 21:48:43');
INSERT INTO `route_permissions` VALUES (31, 'panel/company/update', 2, 'write', 'Actualizar configuración empresa', '2025-08-28 21:48:43');

-- ----------------------------
-- Table structure for schedules
-- ----------------------------
DROP TABLE IF EXISTS `schedules`;
CREATE TABLE `schedules`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `nombre` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `descripcion` varchar(500) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `time_in` time NOT NULL,
  `time_out` time NOT NULL,
  `salida_almuerzo` time NULL DEFAULT NULL COMMENT 'Hora programada de salida a almuerzo',
  `entrada_almuerzo` time NULL DEFAULT NULL COMMENT 'Hora programada de entrada despu├®s de almuerzo',
  `time_in_tolerance_before` smallint UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Minutos antes de entrada permitidos (ej: 30 = puede entrar hasta 30 min antes)',
  `time_in_tolerance_after` smallint UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Minutos despu├®s de entrada permitidos sin tardanza (ej: 15 = puede llegar 15 min tarde sin penalizaci├│n)',
  `time_out_tolerance_before` smallint UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Minutos antes de salida permitidos sin penalizaci├│n',
  `time_out_tolerance_after` smallint UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Minutos despu├®s de salida permitidos (para horas extras aprobables)',
  `lunch_out_tolerance_before` smallint UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Minutos antes de salida a almuerzo permitidos',
  `lunch_out_tolerance_after` smallint UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Minutos despu├®s de salida a almuerzo permitidos',
  `lunch_in_tolerance_before` smallint UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Minutos antes de entrada de almuerzo permitidos',
  `lunch_in_tolerance_after` smallint UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Minutos despu├®s de entrada de almuerzo permitidos sin penalizaci├│n',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `unique_codigo`(`codigo` ASC) USING BTREE,
  INDEX `idx_lunch_schedule`(`salida_almuerzo` ASC, `entrada_almuerzo` ASC) USING BTREE,
  INDEX `idx_schedules_active_tolerances`(`activo` ASC, `time_in_tolerance_after` ASC, `time_out_tolerance_after` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of schedules
-- ----------------------------
INSERT INTO `schedules` VALUES (1, 'HORA01', 'REGULAR', 'REGULAR', '08:00:00', '17:00:00', '12:00:00', '13:00:00', 30, 10, 0, 30, 10, 10, 10, 10, 1, '2025-09-10 02:18:32', '2025-11-20 09:19:23');
INSERT INTO `schedules` VALUES (3, 'HORA02', 'TEMPORAL', 'HORARIO TEMPORAL', '10:00:00', '19:00:00', '14:00:00', '15:00:00', 30, 15, 15, 30, 15, 15, 15, 30, 1, '2025-11-17 10:54:48', '2025-11-18 19:14:37');

-- ----------------------------
-- Table structure for situaciones
-- ----------------------------
DROP TABLE IF EXISTS `situaciones`;
CREATE TABLE `situaciones`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `activo` tinyint(1) NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `codigo`(`codigo` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 9 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of situaciones
-- ----------------------------
INSERT INTO `situaciones` VALUES (1, 'activo', 'Activo', 'Empleado activo', 1, '2025-08-21 22:52:17', '2025-08-21 22:52:17');
INSERT INTO `situaciones` VALUES (2, 'inactivo', 'Inactivo', 'Empleado inactivo', 1, '2025-08-21 22:52:17', '2025-08-21 22:52:17');
INSERT INTO `situaciones` VALUES (3, 'vacaciones', 'Vacaciones', 'Empleado en vacaciones', 1, '2025-08-21 22:52:17', '2025-08-21 22:52:17');
INSERT INTO `situaciones` VALUES (4, 'licencia', 'Licencia', 'Empleado con licencia', 1, '2025-08-21 22:52:17', '2025-08-21 22:52:17');
INSERT INTO `situaciones` VALUES (5, 'suspension', 'Suspensión', 'Empleado suspendido', 1, '2025-08-21 22:52:17', '2025-08-21 22:52:17');
INSERT INTO `situaciones` VALUES (6, 'baja', 'Baja', 'Empleado dado de baja', 1, '2025-08-21 22:52:17', '2025-08-21 22:52:17');
INSERT INTO `situaciones` VALUES (8, 'XIII', 'decimo', 'decimo', 0, '2025-09-08 10:02:15', '2025-09-08 13:02:17');

-- ----------------------------
-- Table structure for temp_planilla_detalle
-- ----------------------------
DROP TABLE IF EXISTS `temp_planilla_detalle`;
CREATE TABLE `temp_planilla_detalle`  (
  `id` int NOT NULL DEFAULT 0,
  `planilla_cabecera_id` int NOT NULL,
  `employee_id` int NOT NULL,
  `concepto_id` int NOT NULL,
  `monto` decimal(10, 2) NOT NULL,
  `tipo` enum('A','D','P') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `organigrama_id` int NULL DEFAULT NULL,
  `organigrama_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `position_id` int NULL DEFAULT NULL,
  `schedule_id` int NULL DEFAULT NULL,
  `firstname` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `lastname` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_transaccion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `valores_editados_manual` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `conceptos_editados_manual` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `cargo_id` int NULL DEFAULT NULL,
  `funcion_id` int NULL DEFAULT NULL,
  `partida_id` int NULL DEFAULT NULL,
  `referencia_valor` decimal(10, 2) NULL DEFAULT NULL COMMENT 'Valor de referencia para cálculos según unidad del concepto (días, horas, monto, %)'
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of temp_planilla_detalle
-- ----------------------------

-- ----------------------------
-- Table structure for tipos_acumulados
-- ----------------------------
DROP TABLE IF EXISTS `tipos_acumulados`;
CREATE TABLE `tipos_acumulados`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Código único del tipo (XIII_MES, VACACIONES, etc)',
  `descripcion` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Descripción del tipo de acumulado',
  `periodicidad` enum('ANUAL','MENSUAL','ESPECIAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ANUAL' COMMENT 'Frecuencia del acumulado',
  `reinicia_automaticamente` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Si se reinicia cada período',
  `activo` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Si está activo para usar',
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT 'Notas adicionales',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fecha_inicio_periodo` date NULL DEFAULT NULL COMMENT 'Fecha de inicio del período de acumulación',
  `fecha_fin_periodo` date NULL DEFAULT NULL COMMENT 'Fecha de fin del período de acumulación',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `codigo`(`codigo` ASC) USING BTREE,
  INDEX `idx_codigo`(`codigo` ASC) USING BTREE,
  INDEX `idx_activo`(`activo` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 24 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of tipos_acumulados
-- ----------------------------
INSERT INTO `tipos_acumulados` VALUES (1, 'XIII_MES', 'Décimo Tercer Mes', 'ESPECIAL', 1, 1, NULL, '2025-09-15 13:58:11', '2025-09-16 23:20:02', '2024-12-16', '2025-04-15');
INSERT INTO `tipos_acumulados` VALUES (15, 'SALARIO_BASE', 'Acumulado de Salario Base', 'ANUAL', 1, 1, NULL, '2025-09-17 10:32:20', '2025-09-27 12:22:51', NULL, NULL);
INSERT INTO `tipos_acumulados` VALUES (22, 'CONCEPTO', 'ACUMULADOS POR CONCEPTO', 'ESPECIAL', 0, 1, NULL, '2025-09-26 19:02:15', '2025-09-26 19:02:15', NULL, NULL);

-- ----------------------------
-- Table structure for tipos_planilla
-- ----------------------------
DROP TABLE IF EXISTS `tipos_planilla`;
CREATE TABLE `tipos_planilla`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `activo` tinyint(1) NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `codigo`(`codigo` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 10 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of tipos_planilla
-- ----------------------------
INSERT INTO `tipos_planilla` VALUES (1, 'COMP', 'COMPANY', 'COMPANY', 1, '2025-08-21 22:51:54', '2025-09-30 16:48:08');

-- ----------------------------
-- Table structure for vacation_annual_balances
-- ----------------------------
DROP TABLE IF EXISTS `vacation_annual_balances`;
CREATE TABLE `vacation_annual_balances`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `year` int NOT NULL COMMENT 'Año del balance',
  `dias_vacaciones_anuales` int NULL DEFAULT 30 COMMENT 'Días disponibles para este año',
  `dias_pagados_year` decimal(6, 2) NULL DEFAULT 0.00 COMMENT 'Días compensados monetariamente en el año',
  `dias_disfrutados_year` decimal(6, 2) NULL DEFAULT 0.00 COMMENT 'Días disfrutados efectivamente en el año',
  `saldo_disponible_year` decimal(6, 2) NULL DEFAULT 30.00 COMMENT 'Saldo disponible restante del año',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `unique_employee_year`(`employee_id` ASC, `year` ASC) USING BTREE,
  INDEX `idx_vab_employee`(`employee_id` ASC) USING BTREE,
  INDEX `idx_vab_year`(`year` ASC) USING BTREE,
  INDEX `idx_vab_saldo`(`saldo_disponible_year` ASC) USING BTREE,
  INDEX `idx_vab_employee_year_saldo`(`employee_id` ASC, `year` ASC, `saldo_disponible_year` ASC) USING BTREE,
  CONSTRAINT `vacation_annual_balances_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 33 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Balance anual detallado de vacaciones por empleado y año' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for vacation_calculations
-- ----------------------------
DROP TABLE IF EXISTS `vacation_calculations`;
CREATE TABLE `vacation_calculations`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `vacation_request_id` int NOT NULL,
  `calculation_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'DAYS_EARNED, DAYS_TAKEN, BALANCE, COMPENSATION',
  `calculation_base` decimal(12, 2) NOT NULL COMMENT 'Base del cálculo (salario, días, etc)',
  `days_calculated` decimal(6, 2) NOT NULL,
  `amount_calculated` decimal(12, 2) NOT NULL DEFAULT 0.00,
  `formula_used` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'Fórmula aplicada para auditoría',
  `period_start` date NULL DEFAULT NULL COMMENT 'Inicio período de cálculo',
  `period_end` date NULL DEFAULT NULL COMMENT 'Fin período de cálculo',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fk_calc_vacation_request`(`vacation_request_id` ASC) USING BTREE,
  INDEX `idx_calc_type`(`calculation_type` ASC) USING BTREE,
  CONSTRAINT `vacation_calculations_ibfk_1` FOREIGN KEY (`vacation_request_id`) REFERENCES `vacation_requests` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for vacation_calendar
-- ----------------------------
DROP TABLE IF EXISTS `vacation_calendar`;
CREATE TABLE `vacation_calendar`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `day_type` enum('HOLIDAY','NON_WORKING','COMPANY_CLOSURE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nombre del feriado o evento',
  `is_recurring` tinyint(1) NULL DEFAULT 0 COMMENT 'Si se repite anualmente',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `unique_calendar_date`(`date` ASC) USING BTREE,
  INDEX `idx_calendar_date`(`date` ASC) USING BTREE,
  INDEX `idx_calendar_type`(`day_type` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of vacation_calendar
-- ----------------------------

-- ----------------------------
-- Table structure for vacation_policies
-- ----------------------------
DROP TABLE IF EXISTS `vacation_policies`;
CREATE TABLE `vacation_policies`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `policy_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `days_per_year` int NOT NULL DEFAULT 30 COMMENT 'Días por año según legislación',
  `minimum_months` int NOT NULL DEFAULT 11 COMMENT 'Meses mínimos para derecho completo',
  `max_accumulated_days` int NULL DEFAULT 60 COMMENT 'Máximo días acumulables',
  `advance_notice_days` int NULL DEFAULT 15 COMMENT 'Días de anticipación requeridos',
  `min_consecutive_days` int NULL DEFAULT 15 COMMENT 'Mínimo días consecutivos legislación',
  `allow_fractional_days` tinyint(1) NULL DEFAULT 0 COMMENT 'Permitir días fraccionados',
  `compensation_allowed` tinyint(1) NULL DEFAULT 1 COMMENT 'Permitir compensación monetaria',
  `is_default` tinyint(1) NULL DEFAULT 0 COMMENT 'Política por defecto',
  `active` tinyint(1) NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_policy_default`(`is_default` ASC) USING BTREE,
  INDEX `idx_policy_active`(`active` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of vacation_policies
-- ----------------------------
INSERT INTO `vacation_policies` VALUES (1, 'Legislación Panamá Standard', 'Política estándar según Código de Trabajo de Panamá: 30 días por cada 11 meses trabajados', 30, 11, 60, 15, 15, 0, 1, 1, 1, '2025-09-24 12:05:17', '2025-09-24 12:05:17');

-- ----------------------------
-- Table structure for vacation_requests
-- ----------------------------
DROP TABLE IF EXISTS `vacation_requests`;
CREATE TABLE `vacation_requests`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `request_date` date NOT NULL DEFAULT (curdate()),
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` int NOT NULL,
  `business_days` int NOT NULL COMMENT 'Días hábiles solicitados',
  `vacation_type` enum('ANNUAL','ACCUMULATED','COMPENSATION','PROPORTIONAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ANNUAL',
  `status` enum('PENDING','APPROVED','REJECTED','TAKEN','CANCELLED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'PENDING',
  `approved_by` int NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `comments` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `years_worked` decimal(6, 2) NOT NULL DEFAULT 0.00,
  `months_worked_current_year` int NULL DEFAULT 0,
  `accumulated_days` decimal(8, 2) NULL DEFAULT 0.00 COMMENT 'Días acumulados disponibles',
  `available_days` decimal(8, 2) NULL DEFAULT 0.00 COMMENT 'Días disponibles antes solicitud',
  `remaining_days` decimal(8, 2) NULL DEFAULT 0.00 COMMENT 'Días restantes después solicitud',
  `daily_salary` decimal(12, 2) NULL DEFAULT 0.00 COMMENT 'Salario diario para compensación',
  `compensation_amount` decimal(12, 2) NULL DEFAULT 0.00 COMMENT 'Monto compensación si aplica',
  `payroll_id` int NULL DEFAULT NULL COMMENT 'ID de la planilla generada para esta solicitud',
  `dias_vacaciones_anuales` int NULL DEFAULT 30,
  `dias_solicitados_pagar` int NULL DEFAULT 0,
  `dias_solicitados_disfrute` int NULL DEFAULT 0,
  `ano_vacaciones` int NULL DEFAULT NULL,
  `periodo_vacaciones` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `dias_calculados_fechas` int NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fk_vacation_employee`(`employee_id` ASC) USING BTREE,
  INDEX `fk_vacation_approver`(`approved_by` ASC) USING BTREE,
  INDEX `idx_vacation_dates`(`start_date` ASC, `end_date` ASC) USING BTREE,
  INDEX `idx_vacation_status`(`status` ASC) USING BTREE,
  INDEX `idx_vacation_employee_status`(`employee_id` ASC, `status` ASC) USING BTREE,
  INDEX `idx_vacation_date_range`(`start_date` ASC, `end_date` ASC, `status` ASC) USING BTREE,
  INDEX `idx_vr_ano_periodo`(`ano_vacaciones` ASC, `periodo_vacaciones` ASC) USING BTREE,
  INDEX `idx_vr_employee_ano`(`employee_id` ASC, `ano_vacaciones` ASC, `status` ASC) USING BTREE,
  INDEX `idx_vr_dias_calculados`(`dias_calculados_fechas` ASC) USING BTREE,
  INDEX `idx_payroll_id`(`payroll_id` ASC) USING BTREE,
  CONSTRAINT `fk_vacation_requests_payroll` FOREIGN KEY (`payroll_id`) REFERENCES `planilla_cabecera` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `vacation_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `vacation_requests_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `admin` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- View structure for v_active_alerts
-- ----------------------------
DROP VIEW IF EXISTS `v_active_alerts`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_active_alerts` AS select `aa`.`id` AS `id`,`aa`.`employee_id` AS `employee_id`,`aa`.`attendance_id` AS `attendance_id`,`aa`.`calculation_id` AS `calculation_id`,`aa`.`alert_type` AS `alert_type`,`aa`.`severity` AS `severity`,`aa`.`title` AS `title`,`aa`.`message` AS `message`,`aa`.`article_reference` AS `article_reference`,`aa`.`metadata` AS `metadata`,`aa`.`date` AS `date`,`aa`.`period_start` AS `period_start`,`aa`.`period_end` AS `period_end`,`aa`.`status` AS `status`,`aa`.`acknowledged_by` AS `acknowledged_by`,`aa`.`acknowledged_at` AS `acknowledged_at`,`aa`.`resolved_at` AS `resolved_at`,`aa`.`notes` AS `notes`,`aa`.`created_at` AS `created_at`,`aa`.`updated_at` AS `updated_at`,`e`.`firstname` AS `firstname`,`e`.`lastname` AS `lastname`,`e`.`employee_id` AS `dni`,concat(`e`.`firstname`,' ',`e`.`lastname`) AS `employee_name`,`u`.`username` AS `acknowledged_by_username` from ((`attendance_alerts` `aa` join `employees` `e` on((`aa`.`employee_id` = `e`.`id`))) left join `admin` `u` on((`aa`.`acknowledged_by` = `u`.`id`))) where (`aa`.`status` in ('PENDING','ACKNOWLEDGED')) order by field(`aa`.`severity`,'CRITICAL','WARNING','INFO'),`aa`.`date` desc;

-- ----------------------------
-- View structure for v_active_attendance_mappings
-- ----------------------------
DROP VIEW IF EXISTS `v_active_attendance_mappings`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_active_attendance_mappings` AS select `acm`.`id` AS `id`,`acm`.`mapping_name` AS `mapping_name`,`acm`.`mapping_type` AS `mapping_type`,`c`.`concepto` AS `concepto_codigo`,`c`.`descripcion` AS `concepto_descripcion`,`acm`.`formula_multiplicador` AS `formula_multiplicador`,`acm`.`valor_fijo` AS `valor_fijo`,`tp`.`descripcion` AS `tipo_planilla`,`sit`.`descripcion` AS `situacion`,`acm`.`priority` AS `priority`,`acm`.`descripcion` AS `descripcion` from (((`attendance_concepts_mapping` `acm` join `concepto` `c` on((`acm`.`concepto_id` = `c`.`id`))) left join `tipos_planilla` `tp` on((`acm`.`tipo_planilla_id` = `tp`.`id`))) left join `situaciones` `sit` on((`acm`.`situacion_id` = `sit`.`id`))) where (`acm`.`is_active` = 1) order by `acm`.`priority`,`acm`.`mapping_type`;

-- ----------------------------
-- View structure for v_acumulados_anuales_empleado
-- ----------------------------
DROP VIEW IF EXISTS `v_acumulados_anuales_empleado`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_acumulados_anuales_empleado` AS select `e`.`id` AS `employee_id`,concat(`e`.`firstname`,' ',`e`.`lastname`) AS `empleado_nombre`,`e`.`document_id` AS `cedula`,`ape`.`ano` AS `ano`,`ape`.`concepto_id` AS `concepto_id`,`c`.`descripcion` AS `concepto_descripcion`,`c`.`tipo_concepto` AS `tipo`,sum(`ape`.`monto`) AS `total_anual`,count(`ape`.`planilla_id`) AS `cantidad_planillas` from ((`acumulados_por_empleado` `ape` join `employees` `e` on((`ape`.`employee_id` = `e`.`id`))) join `concepto` `c` on((`ape`.`concepto_id` = `c`.`id`))) group by `e`.`id`,`ape`.`ano`,`ape`.`concepto_id` order by `e`.`firstname`,`e`.`lastname`,`ape`.`ano`,`c`.`descripcion`;

-- ----------------------------
-- View structure for v_alerts_by_type
-- ----------------------------
DROP VIEW IF EXISTS `v_alerts_by_type`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_alerts_by_type` AS select `aa`.`alert_type` AS `alert_type`,`aa`.`severity` AS `severity`,count(0) AS `total_count`,sum((case when (`aa`.`status` = 'PENDING') then 1 else 0 end)) AS `pending_count`,sum((case when (`aa`.`status` = 'ACKNOWLEDGED') then 1 else 0 end)) AS `acknowledged_count`,sum((case when (`aa`.`status` = 'RESOLVED') then 1 else 0 end)) AS `resolved_count`,sum((case when (`aa`.`status` = 'DISMISSED') then 1 else 0 end)) AS `dismissed_count`,min(`aa`.`date`) AS `first_occurrence`,max(`aa`.`date`) AS `last_occurrence` from `attendance_alerts` `aa` where (`aa`.`created_at` >= (curdate() - interval 30 day)) group by `aa`.`alert_type`,`aa`.`severity` order by field(`aa`.`severity`,'CRITICAL','WARNING','INFO'),`total_count` desc;

-- ----------------------------
-- View structure for v_attendance_detail_with_lunch
-- ----------------------------
DROP VIEW IF EXISTS `v_attendance_detail_with_lunch`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_attendance_detail_with_lunch` AS select `ad`.`id` AS `id`,`ad`.`header_id` AS `header_id`,`ad`.`employee_id` AS `employee_id`,`e`.`employee_id` AS `employee_code`,concat(`e`.`firstname`,' ',`e`.`lastname`) AS `employee_name`,`ad`.`schedule_id` AS `schedule_id`,`s`.`nombre` AS `schedule_name`,`ad`.`time_in` AS `entrada`,`ad`.`lunch_out` AS `salida_almuerzo`,`ad`.`lunch_in` AS `entrada_almuerzo`,`ad`.`time_out` AS `salida`,`ad`.`scheduled_time_in` AS `scheduled_time_in`,`ad`.`scheduled_lunch_out` AS `scheduled_lunch_out`,`ad`.`scheduled_lunch_in` AS `scheduled_lunch_in`,`ad`.`scheduled_time_out` AS `scheduled_time_out`,`ad`.`hours_worked` AS `hours_worked`,`ad`.`lunch_duration_minutes` AS `lunch_duration_minutes`,`ad`.`lunch_exceeded_minutes` AS `lunch_exceeded_minutes`,`ad`.`tardiness_minutes` AS `tardiness_minutes`,`ad`.`early_departure_minutes` AS `early_departure_minutes`,`ad`.`status` AS `status`,`ad`.`created_at` AS `created_at` from ((`attendance_detail` `ad` left join `employees` `e` on((`e`.`id` = `ad`.`employee_id`))) left join `schedules` `s` on((`s`.`id` = `ad`.`schedule_id`))) order by `ad`.`created_at` desc;

-- ----------------------------
-- View structure for v_critical_alerts
-- ----------------------------
DROP VIEW IF EXISTS `v_critical_alerts`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_critical_alerts` AS select `aa`.`id` AS `id`,`aa`.`employee_id` AS `employee_id`,`aa`.`attendance_id` AS `attendance_id`,`aa`.`calculation_id` AS `calculation_id`,`aa`.`alert_type` AS `alert_type`,`aa`.`severity` AS `severity`,`aa`.`title` AS `title`,`aa`.`message` AS `message`,`aa`.`article_reference` AS `article_reference`,`aa`.`metadata` AS `metadata`,`aa`.`date` AS `date`,`aa`.`period_start` AS `period_start`,`aa`.`period_end` AS `period_end`,`aa`.`status` AS `status`,`aa`.`acknowledged_by` AS `acknowledged_by`,`aa`.`acknowledged_at` AS `acknowledged_at`,`aa`.`resolved_at` AS `resolved_at`,`aa`.`notes` AS `notes`,`aa`.`created_at` AS `created_at`,`aa`.`updated_at` AS `updated_at`,`e`.`firstname` AS `firstname`,`e`.`lastname` AS `lastname`,`e`.`employee_id` AS `dni`,concat(`e`.`firstname`,' ',`e`.`lastname`) AS `employee_name`,(to_days(curdate()) - to_days(`aa`.`date`)) AS `days_pending` from (`attendance_alerts` `aa` join `employees` `e` on((`aa`.`employee_id` = `e`.`id`))) where ((`aa`.`severity` = 'CRITICAL') and (`aa`.`status` in ('PENDING','ACKNOWLEDGED'))) order by `aa`.`date` desc;

-- ----------------------------
-- View structure for v_duplicate_records
-- ----------------------------
DROP VIEW IF EXISTS `v_duplicate_records`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_duplicate_records` AS select `r`.`id` AS `id`,`r`.`raw_data_id` AS `raw_data_id`,`r`.`external_id` AS `external_id`,`r`.`employee_id` AS `employee_id`,`r`.`timestamp` AS `timestamp`,`r`.`punch_date` AS `punch_date`,`r`.`punch_time` AS `punch_time`,`r`.`punch_type` AS `punch_type`,`r`.`device_id` AS `device_id`,`r`.`device_serial` AS `device_serial`,`r`.`source` AS `source`,`r`.`is_processed` AS `is_processed`,`r`.`processed_at` AS `processed_at`,`r`.`detail_id` AS `detail_id`,`r`.`is_duplicate` AS `is_duplicate`,`r`.`duplicate_of` AS `duplicate_of`,`r`.`record_hash` AS `record_hash`,`r`.`metadata` AS `metadata`,`r`.`notes` AS `notes`,`r`.`created_at` AS `created_at`,`r`.`updated_at` AS `updated_at`,concat(`e`.`firstname`,' ',`e`.`lastname`) AS `employee_name`,`e`.`employee_id` AS `employee_code`,`orig`.`timestamp` AS `original_timestamp` from ((`attendance_records` `r` join `employees` `e` on((`r`.`employee_id` = `e`.`id`))) left join `attendance_records` `orig` on((`r`.`duplicate_of` = `orig`.`id`))) where (`r`.`is_duplicate` = 1) order by `r`.`created_at` desc;

-- ----------------------------
-- View structure for v_employee_alert_stats
-- ----------------------------
DROP VIEW IF EXISTS `v_employee_alert_stats`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_employee_alert_stats` AS select `e`.`id` AS `employee_id`,concat(`e`.`firstname`,' ',`e`.`lastname`) AS `employee_name`,count(0) AS `total_alerts`,sum((case when (`aa`.`severity` = 'CRITICAL') then 1 else 0 end)) AS `critical_count`,sum((case when (`aa`.`severity` = 'WARNING') then 1 else 0 end)) AS `warning_count`,sum((case when (`aa`.`severity` = 'INFO') then 1 else 0 end)) AS `info_count`,sum((case when (`aa`.`status` = 'PENDING') then 1 else 0 end)) AS `pending_count`,sum((case when (`aa`.`status` = 'RESOLVED') then 1 else 0 end)) AS `resolved_count`,max(`aa`.`date`) AS `last_alert_date` from (`employees` `e` left join `attendance_alerts` `aa` on((`e`.`id` = `aa`.`employee_id`))) where (`aa`.`created_at` >= (curdate() - interval 30 day)) group by `e`.`id`,`e`.`firstname`,`e`.`lastname` having (`total_alerts` > 0) order by `critical_count` desc,`warning_count` desc;

-- ----------------------------
-- View structure for v_employees_legal_risk
-- ----------------------------
DROP VIEW IF EXISTS `v_employees_legal_risk`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_employees_legal_risk` AS select `e`.`id` AS `employee_id`,`e`.`employee_id` AS `employee_code`,concat(`e`.`firstname`,' ',`e`.`lastname`) AS `employee_name`,`pas`.`planilla_cabecera_id` AS `planilla_cabecera_id`,`pas`.`legal_risk_level` AS `legal_risk_level`,`pas`.`legal_violations_count` AS `legal_violations_count`,`pas`.`legal_warnings_count` AS `legal_warnings_count`,`pas`.`critical_alerts` AS `critical_alerts`,`pas`.`legal_compliance_notes` AS `legal_compliance_notes`,`pas`.`period_start` AS `period_start`,`pas`.`period_end` AS `period_end` from (`employees` `e` join `payroll_attendance_summary` `pas` on((`e`.`id` = `pas`.`employee_id`))) where ((`pas`.`legal_compliant` = 0) or (`pas`.`legal_risk_level` in ('ALTO','CRÔö£├¼TICO'))) order by (case `pas`.`legal_risk_level` when 'CRÔö£├¼TICO' then 1 when 'ALTO' then 2 when 'MEDIO' then 3 when 'BAJO' then 4 else 5 end),`pas`.`legal_violations_count` desc;

-- ----------------------------
-- View structure for v_incomplete_attendance_records
-- ----------------------------
DROP VIEW IF EXISTS `v_incomplete_attendance_records`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_incomplete_attendance_records` AS select `r`.`employee_id` AS `employee_id`,concat(`e`.`firstname`,' ',`e`.`lastname`) AS `employee_name`,`r`.`punch_date` AS `punch_date`,sum((case when (`r`.`punch_type` = 'CHECK_IN') then 1 else 0 end)) AS `check_ins`,sum((case when (`r`.`punch_type` = 'CHECK_OUT') then 1 else 0 end)) AS `check_outs`,count(0) AS `total_punches` from (`attendance_records` `r` join `employees` `e` on((`r`.`employee_id` = `e`.`id`))) where ((`r`.`is_processed` = 0) and (`r`.`is_duplicate` = 0)) group by `r`.`employee_id`,`r`.`punch_date` having ((`check_ins` = 0) or (`check_outs` = 0) or (`check_ins` <> `check_outs`)) order by `r`.`punch_date` desc;

-- ----------------------------
-- View structure for v_overtime_approval_stats
-- ----------------------------
DROP VIEW IF EXISTS `v_overtime_approval_stats`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_overtime_approval_stats` AS select `ac`.`overtime_approved_by` AS `overtime_approved_by`,concat(`a`.`firstname`,' ',`a`.`lastname`) AS `approver_name`,`ac`.`overtime_status` AS `overtime_status`,count(0) AS `total_approvals`,sum(`ac`.`overtime_hours`) AS `total_hours_processed`,min(`ac`.`overtime_approved_at`) AS `first_approval`,max(`ac`.`overtime_approved_at`) AS `last_approval` from (`attendance_calculations` `ac` join `admin` `a` on((`ac`.`overtime_approved_by` = `a`.`id`))) where ((`ac`.`overtime_status` in ('APPROVED','REJECTED')) and (`ac`.`overtime_approved_by` is not null)) group by `ac`.`overtime_approved_by`,`ac`.`overtime_status`,`a`.`firstname`,`a`.`lastname`;

-- ----------------------------
-- View structure for v_overtime_pending_approval
-- ----------------------------
DROP VIEW IF EXISTS `v_overtime_pending_approval`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_overtime_pending_approval` AS select `ac`.`id` AS `calculation_id`,`ac`.`employee_id` AS `employee_id`,concat(`e`.`firstname`,' ',`e`.`lastname`) AS `employee_name`,`e`.`employee_id` AS `employee_code`,`ac`.`date` AS `work_date`,`ac`.`time_in` AS `time_in`,`ac`.`time_out` AS `time_out`,`ac`.`total_hours` AS `total_hours`,`ac`.`regular_hours` AS `regular_hours`,`ac`.`overtime_hours` AS `overtime_hours`,`ac`.`overtime_25_hours` AS `overtime_25_hours`,`ac`.`overtime_50_hours` AS `overtime_50_hours`,`ac`.`overtime_status` AS `overtime_status`,`ac`.`overtime_notes` AS `overtime_notes`,`ac`.`calculated_at` AS `calculated_at`,(to_days(curdate()) - to_days(`ac`.`date`)) AS `days_pending` from (`attendance_calculations` `ac` join `employees` `e` on((`ac`.`employee_id` = `e`.`id`))) where ((`ac`.`overtime_status` = 'PENDING') and (`ac`.`overtime_hours` > 0)) order by `ac`.`date` desc,`e`.`lastname`;

-- ----------------------------
-- View structure for v_payroll_attendance_overview
-- ----------------------------
DROP VIEW IF EXISTS `v_payroll_attendance_overview`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_payroll_attendance_overview` AS select `pc`.`id` AS `planilla_id`,`pc`.`descripcion` AS `planilla_descripcion`,`pc`.`fecha` AS `planilla_fecha`,count(distinct `pas`.`employee_id`) AS `total_empleados`,sum(`pas`.`total_hours_worked`) AS `total_horas_trabajadas`,sum((`pas`.`overtime_hours_25` + `pas`.`overtime_hours_50`)) AS `total_horas_extras`,sum(`pas`.`total_absences`) AS `total_ausencias`,avg(`pas`.`punctuality_score`) AS `promedio_puntualidad`,sum(`pas`.`net_attendance_pay`) AS `total_pago_asistencias`,sum((case when (`pas`.`legal_compliant` = 0) then 1 else 0 end)) AS `empleados_con_violaciones` from (`planilla_cabecera` `pc` left join `payroll_attendance_summary` `pas` on((`pc`.`id` = `pas`.`planilla_cabecera_id`))) group by `pc`.`id`,`pc`.`descripcion`,`pc`.`fecha`;

-- ----------------------------
-- View structure for v_records_stats_by_date
-- ----------------------------
DROP VIEW IF EXISTS `v_records_stats_by_date`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_records_stats_by_date` AS select `attendance_records`.`punch_date` AS `punch_date`,count(0) AS `total_records`,count(distinct `attendance_records`.`employee_id`) AS `total_employees`,sum((case when (`attendance_records`.`punch_type` = 'CHECK_IN') then 1 else 0 end)) AS `check_ins`,sum((case when (`attendance_records`.`punch_type` = 'CHECK_OUT') then 1 else 0 end)) AS `check_outs`,sum((case when (`attendance_records`.`is_processed` = 1) then 1 else 0 end)) AS `processed`,sum((case when (`attendance_records`.`is_processed` = 0) then 1 else 0 end)) AS `pending`,sum((case when (`attendance_records`.`is_duplicate` = 1) then 1 else 0 end)) AS `duplicates` from `attendance_records` group by `attendance_records`.`punch_date` order by `attendance_records`.`punch_date` desc;

-- ----------------------------
-- View structure for v_unprocessed_records
-- ----------------------------
DROP VIEW IF EXISTS `v_unprocessed_records`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_unprocessed_records` AS select `r`.`id` AS `id`,`r`.`raw_data_id` AS `raw_data_id`,`r`.`external_id` AS `external_id`,`r`.`employee_id` AS `employee_id`,`r`.`timestamp` AS `timestamp`,`r`.`punch_date` AS `punch_date`,`r`.`punch_time` AS `punch_time`,`r`.`punch_type` AS `punch_type`,`r`.`device_id` AS `device_id`,`r`.`device_serial` AS `device_serial`,`r`.`source` AS `source`,`r`.`is_processed` AS `is_processed`,`r`.`processed_at` AS `processed_at`,`r`.`detail_id` AS `detail_id`,`r`.`is_duplicate` AS `is_duplicate`,`r`.`duplicate_of` AS `duplicate_of`,`r`.`record_hash` AS `record_hash`,`r`.`metadata` AS `metadata`,`r`.`notes` AS `notes`,`r`.`created_at` AS `created_at`,`r`.`updated_at` AS `updated_at`,concat(`e`.`firstname`,' ',`e`.`lastname`) AS `employee_name`,`e`.`employee_id` AS `employee_code`,`d`.`device_name` AS `device_name` from ((`attendance_records` `r` join `employees` `e` on((`r`.`employee_id` = `e`.`id`))) left join `attendance_devices` `d` on((`r`.`device_id` = `d`.`id`))) where ((`r`.`is_processed` = 0) and (`r`.`is_duplicate` = 0)) order by `r`.`punch_date` desc,`r`.`timestamp`;

-- ----------------------------
-- View structure for v_xiii_mes_empleados
-- ----------------------------
DROP VIEW IF EXISTS `v_xiii_mes_empleados`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_xiii_mes_empleados` AS select `e`.`id` AS `employee_id`,concat(`e`.`firstname`,' ',`e`.`lastname`) AS `empleado_nombre`,`e`.`document_id` AS `cedula`,`ape`.`ano` AS `ano`,sum((case when (`c`.`id` in (1,2,3)) then `ape`.`monto` else 0 end)) AS `salario_anual`,(sum((case when (`c`.`id` in (1,2,3)) then `ape`.`monto` else 0 end)) / 3) AS `xiii_mes_teorico`,(sum((case when (`c`.`id` in (1,2,3)) then `ape`.`monto` else 0 end)) / 3) AS `xiii_mes_final` from ((`acumulados_por_empleado` `ape` join `employees` `e` on((`ape`.`employee_id` = `e`.`id`))) join `concepto` `c` on((`ape`.`concepto_id` = `c`.`id`))) where (`c`.`id` in (1,2,3)) group by `e`.`id`,`ape`.`ano` order by `e`.`firstname`,`e`.`lastname`,`ape`.`ano`;



SET FOREIGN_KEY_CHECKS = 1;
