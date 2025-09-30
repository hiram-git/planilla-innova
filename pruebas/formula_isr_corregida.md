# FÓRMULA ISR CORREGIDA - PANAMÁ

## Variables Base
```
salario_anual = SALARIO*13
gr_anual = GASTOS_REPRESENTACION*13
deduc_pers = SI(LEFT(CLAVE_SS, 1) = "E", 800, 0)
```

## Para el cálculo de ISR entre B/.11,001 - B/.50,000 (TRAMO 1)
```
neto_gravable = salario_anual - deduc_pers
saldo_gravable = SI(neto_gravable > 11000, neto_gravable - 11000, 0)
isr_anual = saldo_gravable * 0.15
isr_mensual = isr_anual/13
isr_quincenal = isr_mensual/2
```

## Para el cálculo de ISR > B/.50,000 (TRAMO 2)
```
saldo_excedente = SI(salario_anual > 50000, salario_anual - 50000, 0)
excedente_gravable = SI(saldo_excedente > 0, saldo_excedente * 0.25, 0)
exceso_adicional = SI(excedente_gravable > 0, excedente_gravable + 5850, 0)
exceso_anual = SI(exceso_adicional > 0, exceso_adicional/13, 0)
exceso_quincenal = SI(exceso_anual > 0, exceso_anual/2, 0)
```

## Monto Final ISR Quincenal
```
monto = SI(exceso_quincenal > 0, exceso_quincenal, isr_quincenal)
```

---

## CORRECCIONES APLICADAS:

### ✅ **Error 1 Corregido** (Línea 10 original):
- **ANTES**: `isr_quincenal = isr_quincenal/2`
- **AHORA**: `isr_quincenal = isr_mensual/2`

### ✅ **Error 2 Corregido** (Deducción Personal):
- **ANTES**: `deduc_pers = SI("CLAVE_SS" = "E01" || "CLAVE_SS"="E1", 800, 0)`
- **AHORA**: `deduc_pers = SI(LEFT(CLAVE_SS, 1) = "E", 800, 0)`

### ⚠️ **Pendiente**: Gastos de Representación
- La variable `gr_anual` se define pero no se usa en los cálculos
- Si los gastos de representación también están gravados, se debería incluir en `salario_anual`

---

## NOTAS IMPORTANTES:

1. **Deducción Personal**: Se aplica B/.800 a empleados cuya clave de seguro social comience con "E"
2. **Tramo 1**: 15% sobre exceso de B/.11,000 hasta B/.50,000
3. **Tramo 2**: 25% sobre exceso de B/.50,000 + B/.5,850 base
4. **Periodicidad**: Cálculo quincenal (cada 15 días)

---

## RESULTADOS DE PRUEBA CON BASE DE DATOS REAL:

- **Total empleados analizados**: 14
- **Empleados con deducción personal**: 5 (claves que comienzan con "E")
- **Total ISR quincenal**: B/.542.76
- **Ahorro por deducción personal**: B/.23.08 quincenal

Ver detalles completos en: `pruebas/resultados_isr_final.txt`