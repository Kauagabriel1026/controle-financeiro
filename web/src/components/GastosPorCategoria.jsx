import { moeda } from '../formato.js'

/**
 * Barras horizontais com o total de contas a pagar do mês em cada categoria,
 * da maior para a menor. A barra mais longa é a da categoria que mais pesa.
 */
export default function GastosPorCategoria({ gastos }) {
  if (gastos.categorias.length === 0) {
    return <p className="vazio">Nenhuma conta a pagar com vencimento neste mês.</p>
  }

  const maior = Number(gastos.categorias[0].total)

  return (
    <ul className="gastos">
      {gastos.categorias.map((item) => (
        <li key={item.categoria}>
          <span>{item.categoria}</span>
          <span className="trilho" aria-hidden="true">
            <span className="preenchido" style={{ width: `${(Number(item.total) / maior) * 100}%` }} />
          </span>
          <span className="valor">{moeda(item.total)}</span>
          <span className="parte">{item.percentual.toLocaleString('pt-BR')}%</span>
        </li>
      ))}
    </ul>
  )
}
