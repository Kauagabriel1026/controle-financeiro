const MESES = [
  'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
  'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro',
]

const formatoMoeda = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' })

/** "1500.5" -> "R$ 1.500,50" */
export function moeda(valor) {
  return formatoMoeda.format(Number(valor))
}

/** 1500 -> "R$ 1,5 mil" — para os rótulos do eixo do gráfico. */
export function moedaCurta(valor) {
  if (Math.abs(valor) >= 1000) {
    return `R$ ${(valor / 1000).toLocaleString('pt-BR', { maximumFractionDigits: 2 })} mil`
  }

  return `R$ ${valor.toLocaleString('pt-BR', { maximumFractionDigits: 0 })}`
}

/** "2026-11-10" -> "10/11/2026" */
export function dataBR(iso) {
  const [ano, mes, dia] = iso.split('-')

  return `${dia}/${mes}/${ano}`
}

/** "2026-10" -> "Outubro de 2026" */
export function nomeDoMes(anoMes) {
  const [ano, mes] = anoMes.split('-')
  const nome = MESES[Number(mes) - 1]

  return `${nome[0].toUpperCase()}${nome.slice(1)} de ${ano}`
}

/** "2026-10" -> "out/26" */
export function mesCurto(anoMes) {
  const [ano, mes] = anoMes.split('-')

  return `${MESES[Number(mes) - 1].slice(0, 3)}/${ano.slice(2)}`
}

/** Data de hoje no fuso do navegador, como "2026-10-09". */
export function hojeISO(agora = new Date()) {
  const mes = String(agora.getMonth() + 1).padStart(2, '0')
  const dia = String(agora.getDate()).padStart(2, '0')

  return `${agora.getFullYear()}-${mes}-${dia}`
}

/** "2026-10-09" -> "2026-10" */
export function mesDe(iso) {
  return iso.slice(0, 7)
}

/** "2026-02" -> { de: "2026-02-01", ate: "2026-02-28" } */
export function limitesDoMes(anoMes) {
  const [ano, mes] = anoMes.split('-').map(Number)
  const ultimoDia = new Date(ano, mes, 0).getDate()

  return { de: `${anoMes}-01`, ate: `${anoMes}-${String(ultimoDia).padStart(2, '0')}` }
}

/** somarMeses("2026-11", 3) -> "2027-02" */
export function somarMeses(anoMes, quantidade) {
  const [ano, mes] = anoMes.split('-').map(Number)
  const data = new Date(ano, mes - 1 + quantidade, 1)

  return `${data.getFullYear()}-${String(data.getMonth() + 1).padStart(2, '0')}`
}

/** 160000 -> "1.600,00" — texto mostrado no campo de valor. */
export function centavosParaTexto(centavos) {
  return (centavos / 100).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

/** "1500.10" (como a API devolve) -> 150010 */
export function valorParaCentavos(valor) {
  return Math.round(Number(valor) * 100)
}

/** 150010 -> "1500.10" (como a API espera) */
export function centavosParaValor(centavos) {
  return (centavos / 100).toFixed(2)
}

/** "kaua gabriel ricardo ribeiro" -> "KR": primeira letra do primeiro e do último nome. */
export function iniciais(nome) {
  const partes = nome.trim().split(/\s+/).filter(Boolean)
  const letras = partes.length > 1 ? partes[0][0] + partes.at(-1)[0] : (partes[0] ?? '?').slice(0, 2)

  return letras.toUpperCase()
}

/** "kaua gabriel" -> "Kaua": o primeiro nome, com a inicial maiúscula. */
export function primeiroNome(nome) {
  const primeiro = nome.trim().split(/\s+/)[0] ?? ''

  return primeiro.charAt(0).toUpperCase() + primeiro.slice(1)
}
