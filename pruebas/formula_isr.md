salario_anual = SALARIO*13
gr_anual = GASTOS_REPRESENTACION*13
deduc_pers = SI("CLAVE_SS" = "E01" || "CLAVE_SS"="E1", 800, 0)

#Para el cálculo de 11001 >= ISR  <= 50000 
neto_gravable =  salario_anual - deduc_pers
saldo_gravable =  neto_gravable-11000
isr_anual = saldo_gravable * 0.15
isr_mensual = isr_anual/13
isr_quincenal = isr_quincenal/2

#Para el cálculo de ISR > 50000

saldo_excedente = SI("salario_anual>50000",salario_anual-(50000),0)
excendente_gravable = SI("saldo_excedente>0", saldo_excedente*0.25, 0)
exceso_adicional = SI("excendente_gravable >0", excendente_gravable +5850, 0)
exceso_anual = SI("exceso_adicional >0", exceso_adicional/13, 0)
exceso_quincenal = SI("exceso_anual>0", exceso_anual/2, 0)
monto = SI("exceso_quincenal > 0", exceso_quincenal , isr_quincenal)