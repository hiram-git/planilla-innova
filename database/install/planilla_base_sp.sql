
-- ----------------------------
-- Procedure structure for sp_daily_alerts_summary
-- ----------------------------
DROP PROCEDURE IF EXISTS `sp_daily_alerts_summary`;
delimiter ;;
CREATE PROCEDURE `sp_daily_alerts_summary`(IN p_date DATE)
BEGIN
    SELECT
        COUNT(*) as total_alerts,
        SUM(CASE WHEN severity = 'CRITICAL' THEN 1 ELSE 0 END) as critical_count,
        SUM(CASE WHEN severity = 'WARNING' THEN 1 ELSE 0 END) as warning_count,
        SUM(CASE WHEN severity = 'INFO' THEN 1 ELSE 0 END) as info_count,
        SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending_count,
        COUNT(DISTINCT employee_id) as affected_employees,
        COUNT(DISTINCT alert_type) as different_alert_types
    FROM attendance_alerts
    WHERE date = p_date;
END
;;
delimiter ;


-- ----------------------------
-- Procedure structure for sp_get_employee_active_alerts
-- ----------------------------
DROP PROCEDURE IF EXISTS `sp_get_employee_active_alerts`;
delimiter ;;
CREATE PROCEDURE `sp_get_employee_active_alerts`(IN p_employee_id INT,
    IN p_severity VARCHAR(20))
BEGIN
    SELECT
        aa.*,
        DATEDIFF(CURDATE(), aa.date) as days_pending
    FROM attendance_alerts aa
    WHERE aa.employee_id = p_employee_id
    AND aa.status IN ('PENDING', 'ACKNOWLEDGED')
    AND (p_severity IS NULL OR aa.severity = p_severity)
    ORDER BY
        FIELD(aa.severity, 'CRITICAL', 'WARNING', 'INFO'),
        aa.date DESC;
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for sp_get_grouped_records
-- ----------------------------
DROP PROCEDURE IF EXISTS `sp_get_grouped_records`;
delimiter ;;
CREATE PROCEDURE `sp_get_grouped_records`(IN p_date_from DATE,
    IN p_date_to DATE)
BEGIN
    SELECT
        employee_id,
        punch_date,
        MIN(CASE WHEN punch_type = 'CHECK_IN' THEN timestamp END) as first_check_in,
        MAX(CASE WHEN punch_type = 'CHECK_OUT' THEN timestamp END) as last_check_out,
        COUNT(*) as total_punches,
        GROUP_CONCAT(id ORDER BY timestamp) as record_ids
    FROM attendance_records
    WHERE punch_date BETWEEN p_date_from AND p_date_to
      AND is_processed = 0
      AND is_duplicate = 0
    GROUP BY employee_id, punch_date
    ORDER BY punch_date DESC, employee_id;
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for sp_mark_records_processed
-- ----------------------------
DROP PROCEDURE IF EXISTS `sp_mark_records_processed`;
delimiter ;;
CREATE PROCEDURE `sp_mark_records_processed`(IN p_employee_id INT,
    IN p_punch_date DATE,
    IN p_detail_id INT)
BEGIN
    UPDATE attendance_records
    SET is_processed = 1,
        processed_at = NOW(),
        detail_id = p_detail_id
    WHERE employee_id = p_employee_id
      AND punch_date = p_punch_date
      AND is_processed = 0
      AND is_duplicate = 0;

    SELECT ROW_COUNT() as records_marked;
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for sp_validate_attendance_completeness
-- ----------------------------
DROP PROCEDURE IF EXISTS `sp_validate_attendance_completeness`;
delimiter ;;
CREATE PROCEDURE `sp_validate_attendance_completeness`(IN p_attendance_detail_id INT,
    OUT p_status VARCHAR(20),
    OUT p_missing_punches TEXT)
BEGIN
    DECLARE v_time_in DATETIME;
    DECLARE v_lunch_out DATETIME;
    DECLARE v_lunch_in DATETIME;
    DECLARE v_time_out DATETIME;
    DECLARE v_has_lunch_schedule BOOLEAN;

    
    SELECT
        ad.time_in,
        ad.lunch_out,
        ad.lunch_in,
        ad.time_out,
        (s.salida_almuerzo IS NOT NULL AND s.entrada_almuerzo IS NOT NULL) as has_lunch
    INTO
        v_time_in,
        v_lunch_out,
        v_lunch_in,
        v_time_out,
        v_has_lunch_schedule
    FROM attendance_detail ad
    LEFT JOIN schedules s ON s.id = ad.schedule_id
    WHERE ad.id = p_attendance_detail_id;

    
    SET p_missing_punches = '';

    
    IF v_has_lunch_schedule THEN
        
        IF v_time_in IS NULL THEN
            SET p_missing_punches = CONCAT(p_missing_punches, 'Entrada, ');
        END IF;
        IF v_lunch_out IS NULL THEN
            SET p_missing_punches = CONCAT(p_missing_punches, 'Salida Almuerzo, ');
        END IF;
        IF v_lunch_in IS NULL THEN
            SET p_missing_punches = CONCAT(p_missing_punches, 'Entrada Almuerzo, ');
        END IF;
        IF v_time_out IS NULL THEN
            SET p_missing_punches = CONCAT(p_missing_punches, 'Salida, ');
        END IF;

        
        IF v_time_in IS NOT NULL AND v_lunch_out IS NOT NULL
           AND v_lunch_in IS NOT NULL AND v_time_out IS NOT NULL THEN
            SET p_status = 'COMPLETE';
        ELSEIF v_time_in IS NULL AND v_lunch_out IS NULL
               AND v_lunch_in IS NULL AND v_time_out IS NULL THEN
            SET p_status = 'ABSENT';
        ELSE
            SET p_status = 'INCOMPLETE';
        END IF;
    ELSE
        
        IF v_time_in IS NULL THEN
            SET p_missing_punches = CONCAT(p_missing_punches, 'Entrada, ');
        END IF;
        IF v_time_out IS NULL THEN
            SET p_missing_punches = CONCAT(p_missing_punches, 'Salida, ');
        END IF;

        
        IF v_time_in IS NOT NULL AND v_time_out IS NOT NULL THEN
            SET p_status = 'COMPLETE';
        ELSEIF v_time_in IS NULL AND v_time_out IS NULL THEN
            SET p_status = 'ABSENT';
        ELSE
            SET p_status = 'INCOMPLETE';
        END IF;
    END IF;

    
    IF LENGTH(p_missing_punches) > 0 THEN
        SET p_missing_punches = TRIM(TRAILING ', ' FROM p_missing_punches);
    END IF;
END
;;
delimiter ;