# Plan de Consolidación de Migraciones

## Resumen
- **Total archivos**: 50
- **Periodo**: 2025-09-17 a 2025-09-24
- **Directorios origen**: database, databases, database/migrations

## Migraciones Consolidadas

| # | Archivo Original | Archivo Nuevo | Fecha | Directorio |
|---|------------------|---------------|-------|------------|
| 1 | add_employee_fields.sql | 2025_09_17_0129_employee_fields.sql | 2025-09-17 01:28 | database |
| 2 | company_table.sql | 2025_09_17_0130_company_table.sql | 2025-09-17 01:28 | database |
| 3 | multitenancy_schema.sql | 2025_09_17_0131_multitenancy_schema.sql | 2025-09-17 01:28 | database |
| 4 | payroll_tables.sql | 2025_09_17_0132_payroll_tables.sql | 2025-09-17 01:28 | database |
| 5 | schema.sql | 2025_09_17_0133_schema.sql | 2025-09-17 01:28 | database |
| 6 | tenant_schema.sql | 2025_09_17_0134_tenant_schema.sql | 2025-09-17 01:28 | database |
| 7 | install.php | 2025_09_17_0135_install.sql | 2025-09-17 01:28 | database |
| 8 | add_signature_fields_simple.sql | 2025_09_17_0136_signature_fields_simple.sql | 2025-09-17 01:28 | databases |
| 9 | agregar_permisos_acumulados.sql | 2025_09_17_0137_agregar_permisos_acumulados.sql | 2025-09-17 01:28 | databases |
| 10 | check_companies_structure.sql | 2025_09_17_0138_check_companies_structure.sql | 2025-09-17 01:28 | databases |
| 11 | create_organigrama_simple.sql | 2025_09_17_0139_organigrama_simple.sql | 2025-09-17 01:28 | databases |
| 12 | fix_tipos_acumulados_produccion.sql | 2025_09_17_0140_tipos_acumulados_produccion.sql | 2025-09-17 01:28 | databases |
| 13 | migration_acumulados_fixed.sql | 2025_09_17_0141_acumulados_fixed.sql | 2025-09-17 01:28 | databases |
| 14 | migration_clave_seguro_social.sql | 2025_09_17_0142_clave_seguro_social.sql | 2025-09-17 01:28 | databases |
| 15 | migration_new_acumulados_structure.sql | 2025_09_17_0143_new_acumulados_structure.sql | 2025-09-17 01:28 | databases |
| 16 | migration_v3.1_acumulados_sistema.sql | 2025_09_17_0144_core_acumulados_system.sql | 2025-09-17 01:28 | databases |
| 17 | migration_v3.2.1_solo_campos_firmas.sql | 2025_09_17_0145_signature_fields_only.sql | 2025-09-17 01:28 | databases |
| 18 | migration_v3.2.2_estructura_organizacional.sql | 2025_09_17_0146_organizational_structure.sql | 2025-09-17 01:28 | databases |
| 19 | migration_v3.2_fix_campos_firmas_safe.sql | 2025_09_17_0147_signature_fields_safe.sql | 2025-09-17 01:28 | databases |
| 20 | migration_v3.2_fix_organigrama_safe.sql | 2025_09_17_0148_organizational_chart_safe.sql | 2025-09-17 01:28 | databases |
| 21 | migration_v3.2_reportes_pdf_firmas.sql | 2025_09_17_0149_pdf_reports_signatures.sql | 2025-09-17 01:28 | databases |
| 22 | run_migration_v3.2_complete.sql | 2025_09_17_0150_run_migration_v3_2_complete.sql | 2025-09-17 01:28 | databases |
| 23 | verify_migration_v3.2_status.sql | 2025_09_17_0151_verify_migration_v3_2_status.sql | 2025-09-17 01:28 | databases |
| 24 | create_simple_acumulados_table.php | 2025_09_17_0152_simple_acumulados_table.sql | 2025-09-17 01:28 | databases |
| 25 | create_tipos_acumulados_table.php | 2025_09_17_0153_tipos_acumulados_table.sql | 2025-09-17 01:28 | databases |
| 26 | add_organizational_hierarchy.sql | 2025_09_17_0154_organizational_hierarchy.sql | 2025-09-17 01:28 | database/migrations |
| 27 | add_organizational_hierarchy_fixed.sql | 2025_09_17_0155_organizational_hierarchy_fixed.sql | 2025-09-17 01:28 | database/migrations |
| 28 | add_organizational_hierarchy_safe.sql | 2025_09_17_0156_organizational_hierarchy_safe.sql | 2025-09-17 01:28 | database/migrations |
| 29 | add_organizational_hierarchy_simple.sql | 2025_09_17_0157_organizational_hierarchy_simple.sql | 2025-09-17 01:28 | database/migrations |
| 30 | add_payroll_signature_fields.sql | 2025_09_17_0158_payroll_signature_fields.sql | 2025-09-17 01:28 | database/migrations |
| 31 | create_acumulados_tables.sql | 2025_09_17_0159_acumulados_tables.sql | 2025-09-17 01:28 | database/migrations |
| 32 | fix_planilla_estado_enum.sql | 2025_09_17_0160_planilla_estado_enum.sql | 2025-09-17 01:28 | database/migrations |
| 33 | add_referencia_valor_to_planilla_detalle.sql | 2025_09_17_0266_referencia_valor_to_planilla_detalle.sql | 2025-09-17 02:33 | database/migrations |
| 34 | add_organigrama_id_to_employees.sql | 2025_09_17_1277_organigrama_id_to_employees.sql | 2025-09-17 12:43 | database/migrations |
| 35 | add_logos_to_companies.sql | 2025_09_17_1381_logos_to_companies.sql | 2025-09-17 13:46 | database/migrations |
| 36 | change_organigrama_path_to_id_planilla_detalle.sql | 2025_09_18_1580_change_organigrama_path_to_id_planilla_detalle.sql | 2025-09-18 15:44 | database/migrations |
| 37 | add_tipo_acumulado_to_acumulados_por_empleado.sql | 2025_09_18_1853_tipo_acumulado_to_acumulados_por_empleado.sql | 2025-09-18 18:16 | database/migrations |
| 38 | change_frecuencia_to_id_acumulados_por_empleado.sql | 2025_09_19_2243_change_frecuencia_to_id_acumulados_por_empleado.sql | 2025-09-19 22:05 | database/migrations |
| 39 | add_gastos_representacion_to_employees.sql | 2025_09_20_1141_gastos_representacion_to_employees.sql | 2025-09-20 11:02 | database/migrations |
| 40 | create_liquidation_system.sql | 2025_09_20_1647_employee_liquidation_system.sql | 2025-09-20 16:07 | database/migrations |
| 41 | add_employee_contract_payment_fields.sql | 2025_09_20_2064_employee_contract_payment_fields.sql | 2025-09-20 20:23 | database/migrations |
| 42 | remove_estado_laboral_enum_field.sql | 2025_09_20_2075_remove_estado_laboral_enum_field.sql | 2025-09-20 20:33 | database/migrations |
| 43 | create_vacation_system.sql | 2025_09_21_1746_vacation_management_system.sql | 2025-09-21 17:03 | database/migrations |
| 44 | create_business_calendar.sql | 2025_09_22_1193_panama_business_calendar.sql | 2025-09-22 11:49 | database/migrations |
| 45 | add_liquidation_cancellation_system.sql | 2025_09_23_1457_liquidation_cancellation_system.sql | 2025-09-23 14:12 | database/migrations |
| 46 | complete_liquidation_cancellation_system.sql | 2025_09_23_1459_complete_liquidation_cancellation_system.sql | 2025-09-23 14:13 | database/migrations |
| 47 | improve_liquidation_calculations_structure.sql | 2025_09_23_2267_improve_liquidation_calculations_structure.sql | 2025-09-23 22:20 | database/migrations |
| 48 | add_missing_liquidation_concepts.sql | 2025_09_24_0671_missing_liquidation_concepts.sql | 2025-09-24 06:23 | database/migrations |
| 49 | fix_liquidation_concepts_safe.sql | 2025_09_24_0673_liquidation_concepts_safe.sql | 2025-09-24 06:24 | database/migrations |
| 50 | fix_liquidation_final_clean.sql | 2025_09_24_0706_liquidation_final_clean.sql | 2025-09-24 06:56 | database/migrations |

## Comandos de Migración

```bash
# Ver status
php database/migrations/migration_runner.php --status

# Dry run (no ejecuta)
php database/migrations/migration_runner.php --dry-run

# Ejecutar migraciones
php database/migrations/migration_runner.php

# Ejecutar hasta versión específica
php database/migrations/migration_runner.php --version=3.3.0
```

## Nomenclatura
**Formato**: `YYYY_MM_DD_HHII_nombre_descriptivo.sql`

- `YYYY`: Año
- `MM`: Mes
- `DD`: Día
- `HHII`: Hora y minuto (24h)
- `nombre_descriptivo`: Descripción en snake_case

