import { moeda } from '../formato.js'

/**
 * Total do mês em cada categoria, da maior para a menor, com uma barra
 * proporcional. `tipo` é "pagar" (gastos) ou "receber" (ganhos) e define a cor.
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
    <>
      <p className="total-categorias">
        <strong>{moeda(totais.total)}</strong> em {totais.categorias.length}{' '}
        {totais.categorias.length === 1 ? 'categoria' : 'categorias'}
      </p>

      <ul className="totais">
        {totais.categorias.map((item) => (
          <li key={item.categoria}>
            <span className="nome">{item.categoria}</span>
            <span className="valor">
              {moeda(item.total)}
              <span className="parte">{item.percentual.toLocaleString('pt-BR')}%</span>
            </span>
            <span className="trilho" aria-hidden="true">
              <span className={`preenchido ${tipo}`} style={{ width: `${(Number(item.total) / maior) * 100}%` }} />
            </span>
          </li>
        ))}
      </ul>
    </>
  )
}
