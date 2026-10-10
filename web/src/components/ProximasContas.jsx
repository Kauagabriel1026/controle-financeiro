import { Link } from 'react-router-dom'

import { dataBR, moeda } from '../formato.js'

/** As próximas contas em aberto, da que vence primeiro para a que vence por último. */
export default function ProximasContas({ contas }) {
  if (contas.length === 0) {
    return <p className="vazio">Nenhuma conta em aberto.</p>
  }

  return (
    <>
      <ul className="proximas">
        {contas.map((conta) => (
          <li key={conta.id}>
            <span className="dia">{dataBR(conta.vencimento).slice(0, 5)}</span>
            <span className="descricao">
              <span className="nome">{conta.descricao}</span>
              <span className={`tipo ${conta.tipo}`}>{conta.tipo === 'pagar' ? 'A pagar' : 'A receber'}</span>
            </span>
            <span className="valor">{moeda(conta.valor)}</span>
          </li>
        ))}
      </ul>
      <Link className="botao-texto" to="/contas?status=pendente&mes=todos">Ver todas as contas em aberto</Link>
    </>
  )
}
