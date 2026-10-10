import { useState } from 'react'

import { mesCurto, moeda, moedaCurta, nomeDoMes } from '../formato.js'

const LARGURA = 720
const ALTURA = 300
const MARGEM = { topo: 12, direita: 8, base: 52, esquerda: 76 }
const AREA_L = LARGURA - MARGEM.esquerda - MARGEM.direita
const AREA_A = ALTURA - MARGEM.topo - MARGEM.base
const BARRA = 24
const VAO = 3

/** Arredonda o topo do eixo para um número "redondo": 1.700 -> 2.000. */
function tetoRedondo(valor) {
  if (valor <= 0) return 100

  const potencia = 10 ** Math.floor(Math.log10(valor))

  return [1, 2, 2.5, 5, 10].map((m) => m * potencia).find((teto) => teto >= valor)
}

/** Saldo com sinal para caber embaixo de cada mês: "+R$ 1,3 mil", "−R$ 900". */
function saldoCurto(valor) {
  if (valor === 0) return 'R$ 0'

  return `${valor < 0 ? '−' : '+'}${moedaCurta(Math.abs(valor))}`
}

/** Barra com os cantos de cima arredondados e a base reta, apoiada no eixo. */
function caminhoDaBarra(x, altura) {
  const base = MARGEM.topo + AREA_A
  const raio = Math.min(5, altura)
  const topo = base - altura

  return [
    `M${x},${base}`,
    `V${topo + raio}`,
    `Q${x},${topo} ${x + raio},${topo}`,
    `H${x + BARRA - raio}`,
    `Q${x + BARRA},${topo} ${x + BARRA},${topo + raio}`,
    `V${base}`,
    'Z',
  ].join(' ')
}

export default function GraficoFluxo({ meses }) {
  const [ativo, setAtivo] = useState(null)

  const maior = Math.max(...meses.flatMap((mes) => [Number(mes.receber), Number(mes.pagar)]))

  if (maior === 0) {
    return <p className="vazio">Nenhuma conta com vencimento neste período.</p>
  }

  const teto = tetoRedondo(maior)
  const faixa = AREA_L / meses.length
  const altura = (valor) => (Number(valor) / teto) * AREA_A
  const marcas = [0, teto / 4, teto / 2, (teto * 3) / 4, teto]
  const mesAtivo = ativo === null ? null : meses[ativo]

  return (
    <div className="grafico">
      <ul className="legenda">
        <li><span className="amostra receber" />A receber</li>
        <li><span className="amostra pagar" />A pagar</li>
      </ul>

      <div className="grafico-area">
        <svg viewBox={`0 0 ${LARGURA} ${ALTURA}`} role="img" aria-label="Valores a receber e a pagar por mês">
          {marcas.map((marca) => {
            const y = MARGEM.topo + AREA_A - altura(marca)

            return (
              <g key={marca}>
                <line className="grade" x1={MARGEM.esquerda} x2={LARGURA - MARGEM.direita} y1={y} y2={y} />
                <text className="eixo" x={MARGEM.esquerda - 10} y={y} textAnchor="end" dominantBaseline="middle">
                  {moedaCurta(marca)}
                </text>
              </g>
            )
          })}

          <text className="eixo" x={MARGEM.esquerda - 10} y={ALTURA - 10} textAnchor="end">Saldo</text>

          {meses.map((mes, indice) => {
            const centro = MARGEM.esquerda + faixa * indice + faixa / 2

            return (
              <g key={mes.mes}>
                {indice === ativo && (
                  <rect className="destaque" x={centro - faixa / 2} y={MARGEM.topo} width={faixa} height={AREA_A} />
                )}

                <path className="barra receber" d={caminhoDaBarra(centro - BARRA - VAO / 2, altura(mes.receber))} />
                <path className="barra pagar" d={caminhoDaBarra(centro + VAO / 2, altura(mes.pagar))} />

                <text className="eixo" x={centro} y={ALTURA - 30} textAnchor="middle">{mesCurto(mes.mes)}</text>
                <text className={Number(mes.saldo) < 0 ? 'eixo saldo negativo' : 'eixo saldo'} x={centro} y={ALTURA - 10} textAnchor="middle">
                  {saldoCurto(Number(mes.saldo))}
                </text>

                {/* Área invisível da faixa inteira: alvo de mouse e de teclado maior que as barras. */}
                <rect
                  className="alvo"
                  x={centro - faixa / 2}
                  y={MARGEM.topo}
                  width={faixa}
                  height={AREA_A + MARGEM.base}
                  tabIndex={0}
                  aria-label={`${nomeDoMes(mes.mes)}: a receber ${moeda(mes.receber)}, a pagar ${moeda(mes.pagar)}, saldo ${moeda(mes.saldo)}`}
                  onMouseEnter={() => setAtivo(indice)}
                  onMouseLeave={() => setAtivo(null)}
                  onFocus={() => setAtivo(indice)}
                  onBlur={() => setAtivo(null)}
                />
              </g>
            )
          })}
        </svg>

        {mesAtivo && (
          <div
            className="dica-grafico"
            style={{ left: `${((MARGEM.esquerda + faixa * ativo + faixa / 2) / LARGURA) * 100}%` }}
          >
            <strong>{nomeDoMes(mesAtivo.mes)}</strong>
            <span><span className="amostra receber" />A receber <b>{moeda(mesAtivo.receber)}</b></span>
            <span><span className="amostra pagar" />A pagar <b>{moeda(mesAtivo.pagar)}</b></span>
            <span className="dica-saldo">Saldo <b>{moeda(mesAtivo.saldo)}</b></span>
          </div>
        )}
      </div>
    </div>
  )
}
