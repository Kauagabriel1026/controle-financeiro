/**
 * Quadro com um número de destaque do mês. `tom` define a cor da faixa
 * lateral: receber, pagar, saldo, negativo ou neutro.
 */
export default function Indicador({ rotulo, valor, tom, children }) {
  return (
    <div className={`indicador ${tom}`} role="group" aria-label={rotulo}>
      <span className="rotulo">{rotulo}</span>
      <strong>{valor}</strong>
      {children}
    </div>
  )
}

/** Barra de progresso: quanto de um total já foi recebido ou pago. */
export function Medidor({ parte, total, tipo, legenda }) {
  const fracao = Number(total) > 0 ? Math.min(Number(parte) / Number(total), 1) : 0

  return (
    <>
      <span className="trilho" role="img" aria-label={`${Math.round(fracao * 100)}% do total`}>
        <span className={`preenchido ${tipo}`} style={{ width: `${fracao * 100}%` }} />
      </span>
      <span className="detalhe">{legenda}</span>
    </>
  )
}
