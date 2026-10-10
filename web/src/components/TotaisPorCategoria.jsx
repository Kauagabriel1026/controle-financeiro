import { moeda } from '../formato.js'

/**
 * Barras horizontais com o total do mês em cada categoria, da maior para a
 * menor. `tipo` é "pagar" (gastos) ou "receber" (ganhos) e define a cor.
 */
export default function TotaisPorCategoria({ totais, tipo }) {
  if (totais.categorias.length === 0) {
    return (
      <p className="vazio">
        {tipo === 'pagar' ? 'Nenhuma conta a pagar' : 'Nenhuma conta a receber'} com vencimento neste mês.
      </p>
    )
  }

  const maior = Number(totais.categorias[0].total)

  return (
    <ul className="totais">
      {totais.categorias.map((item) => (
        <li key={item.categoria}>
          <span>{item.categoria}</span>
          <span className="trilho" aria-hidden="true">
            <span className={`preenchido ${tipo}`} style={{ width: `${(Number(item.total) / maior) * 100}%` }} />
          </span>
          <span className="valor">{moeda(item.total)}</span>
          <span className="parte">{item.percentual.toLocaleString('pt-BR')}%</span>
        </li>
      ))}
    </ul>
  )
}
