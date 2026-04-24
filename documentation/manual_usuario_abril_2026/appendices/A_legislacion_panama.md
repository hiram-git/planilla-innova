# Legislación laboral de Panamá aplicada {.unnumbered}

Este apéndice resume los artículos del Código de Trabajo de Panamá que el
sistema aplica automáticamente en sus cálculos.

| Artículo | Materia                        | Aplicación en el sistema                                  |
|----------|--------------------------------|-----------------------------------------------------------|
| Art. 31  | Jornada ordinaria              | 8 h diarias / 48 h semanales como techo                   |
| Art. 35  | Descanso de almuerzo           | Mínimo 30 min, descontado automáticamente                 |
| Art. 38  | Jornada nocturna (6 PM - 6 AM) | Recargo 50 % aplicado en `HORAS_NOCTURNAS()`              |
| Art. 39  | Horas extras                   | +25 % primeras 3 h, +50 % adicionales                     |
| Art. 48  | Domingos y feriados            | Recargo 50 %, calculado en `HORAS_DOMINICALES()`          |
| Art. 213 | Ausencias injustificadas       | Alerta automática tras 3+ ausencias injustificadas/mes    |

> **TODO**: completar con referencias literales a los artículos y ejemplos
> numéricos por cada uno.
