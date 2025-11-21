

-- ----------------------------
-- Triggers structure for table attendance_alerts
-- ----------------------------
DROP TRIGGER IF EXISTS `trg_alert_acknowledged`;
delimiter ;;
CREATE TRIGGER `trg_alert_acknowledged` BEFORE UPDATE ON `attendance_alerts` FOR EACH ROW BEGIN
    IF NEW.status = 'ACKNOWLEDGED' AND OLD.status = 'PENDING' THEN
        IF NEW.acknowledged_at IS NULL THEN
            SET NEW.acknowledged_at = NOW();
        END IF;
    END IF;
END
;;
delimiter ;

-- ----------------------------
-- Triggers structure for table attendance_alerts
-- ----------------------------
DROP TRIGGER IF EXISTS `trg_alert_resolved`;
delimiter ;;
CREATE TRIGGER `trg_alert_resolved` BEFORE UPDATE ON `attendance_alerts` FOR EACH ROW BEGIN
    IF NEW.status = 'RESOLVED' AND OLD.status != 'RESOLVED' THEN
        IF NEW.resolved_at IS NULL THEN
            SET NEW.resolved_at = NOW();
        END IF;
    END IF;
END
;;
delimiter ;

-- ----------------------------
-- Triggers structure for table attendance_calculations
-- ----------------------------
DROP TRIGGER IF EXISTS `trg_overtime_status_auto`;
delimiter ;;
CREATE TRIGGER `trg_overtime_status_auto` BEFORE INSERT ON `attendance_calculations` FOR EACH ROW BEGIN
    IF NEW.overtime_hours = 0 OR NEW.overtime_hours IS NULL THEN
        SET NEW.overtime_status = 'NOT_APPLICABLE';
    ELSEIF NEW.overtime_hours > 0 AND NEW.overtime_status IS NULL THEN
        SET NEW.overtime_status = 'PENDING';
    END IF;
END
;;
delimiter ;

-- ----------------------------
-- Triggers structure for table attendance_calculations
-- ----------------------------
DROP TRIGGER IF EXISTS `trg_overtime_rejection_reason`;
delimiter ;;
CREATE TRIGGER `trg_overtime_rejection_reason` BEFORE UPDATE ON `attendance_calculations` FOR EACH ROW BEGIN
    IF NEW.overtime_status = 'REJECTED' AND
       (NEW.overtime_rejection_reason IS NULL OR NEW.overtime_rejection_reason = '') THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Se requiere raz├│n de rechazo (overtime_rejection_reason) cuando status = REJECTED';
    END IF;

    IF NEW.overtime_status IN ('APPROVED', 'REJECTED') AND
       OLD.overtime_status = 'PENDING' AND
       NEW.overtime_approved_at IS NULL THEN
        SET NEW.overtime_approved_at = NOW();
    END IF;
END
;;
delimiter ;

-- ----------------------------
-- Triggers structure for table attendance_detail
-- ----------------------------
DROP TRIGGER IF EXISTS `trg_calculate_lunch_duration`;
delimiter ;;
CREATE TRIGGER `trg_calculate_lunch_duration` BEFORE UPDATE ON `attendance_detail` FOR EACH ROW BEGIN
    DECLARE scheduled_duration INT;

    
    IF NEW.lunch_out IS NOT NULL AND NEW.lunch_in IS NOT NULL THEN
        SET NEW.lunch_duration_minutes = TIMESTAMPDIFF(MINUTE, NEW.lunch_out, NEW.lunch_in);

        
        IF NEW.scheduled_lunch_out IS NOT NULL AND NEW.scheduled_lunch_in IS NOT NULL THEN
            SET scheduled_duration = TIMESTAMPDIFF(MINUTE,
                TIMESTAMP(DATE(NEW.lunch_out), NEW.scheduled_lunch_out),
                TIMESTAMP(DATE(NEW.lunch_in), NEW.scheduled_lunch_in)
            );

            
            SET NEW.lunch_exceeded_minutes = GREATEST(0, NEW.lunch_duration_minutes - scheduled_duration);
        END IF;
    ELSE
        SET NEW.lunch_duration_minutes = 0;
        SET NEW.lunch_exceeded_minutes = 0;
    END IF;
END
;;
delimiter ;

-- ----------------------------
-- Triggers structure for table attendance_records
-- ----------------------------
DROP TRIGGER IF EXISTS `before_insert_attendance_records`;
delimiter ;;
CREATE TRIGGER `before_insert_attendance_records` BEFORE INSERT ON `attendance_records` FOR EACH ROW BEGIN
    -- Calcular punch_date y punch_time desde timestamp si no están definidos
    IF NEW.punch_date IS NULL THEN
        SET NEW.punch_date = DATE(NEW.timestamp);
    END IF;

    IF NEW.punch_time IS NULL THEN
        SET NEW.punch_time = TIME(NEW.timestamp);
    END IF;

    -- Calcular record_hash si no está definido
    IF NEW.record_hash IS NULL OR NEW.record_hash = '' THEN
        SET NEW.record_hash = MD5(CONCAT(
            NEW.employee_id, '|',
            NEW.timestamp, '|',
            NEW.punch_type
        ));
    END IF;
END
;;
delimiter ;

-- ----------------------------
-- Triggers structure for table attendance_records
-- ----------------------------
DROP TRIGGER IF EXISTS `before_update_attendance_records`;
delimiter ;;
CREATE TRIGGER `before_update_attendance_records` BEFORE UPDATE ON `attendance_records` FOR EACH ROW BEGIN
    -- Si se modifica timestamp, actualizar punch_date y punch_time
    IF NEW.timestamp != OLD.timestamp THEN
        SET NEW.punch_date = DATE(NEW.timestamp);
        SET NEW.punch_time = TIME(NEW.timestamp);

        -- Recalcular hash
        SET NEW.record_hash = MD5(CONCAT(
            NEW.employee_id, '|',
            NEW.timestamp, '|',
            NEW.punch_type
        ));
    END IF;
END
;;
delimiter ;