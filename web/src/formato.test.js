import { describe, expect, it } from 'vitest'

import {
  dataBR, hojeISO, limitesDoMes, mesCurto, mesDe, moeda, moedaCurta, nomeDoMes, somarMeses,
} from './formato.js'

// O Intl separa "R$" do número com um espaço não separável.
const semEspacoEspecial = (texto) => texto.replace(/ /g, ' ')

describe('formato', () => {
  it('formata valores em reais', () => {
    expect(semEspacoEspecial(moeda('1500.5'))).toBe('R$ 1.500,50')
    expect(semEspacoEspecial(moeda('-200'))).toBe('-R$ 200,00')
  })

  it('abrevia valores para o eixo do gráfico', () => {
    expect(moedaCurta(1500)).toBe('R$ 1,5 mil')
    expect(moedaCurta(1250)).toBe('R$ 1,25 mil')
    expect(moedaCurta(800)).toBe('R$ 800')
  })

  it('converte datas e meses', () => {
    expect(dataBR('2026-11-10')).toBe('10/11/2026')
    expect(nomeDoMes('2026-10')).toBe('Outubro de 2026')
    expect(mesCurto('2026-03')).toBe('mar/26')
    expect(mesDe('2026-10-09')).toBe('2026-10')
    expect(hojeISO(new Date(2026, 0, 5))).toBe('2026-01-05')
  })

  it('soma e subtrai meses virando o ano', () => {
    expect(somarMeses('2026-11', 3)).toBe('2027-02')
    expect(somarMeses('2026-01', -1)).toBe('2025-12')
    expect(somarMeses('2026-10', 0)).toBe('2026-10')
  })

  it('calcula o primeiro e o último dia do mês', () => {
    expect(limitesDoMes('2026-02')).toEqual({ de: '2026-02-01', ate: '2026-02-28' })
    expect(limitesDoMes('2028-02')).toEqual({ de: '2028-02-01', ate: '2028-02-29' })
    expect(limitesDoMes('2026-12')).toEqual({ de: '2026-12-01', ate: '2026-12-31' })
  })
})
