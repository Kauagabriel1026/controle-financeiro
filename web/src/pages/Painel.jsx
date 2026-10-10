import { Link } from 'react-router-dom'

import { api } from '../api.js'
import Aviso from '../components/Aviso.jsx'
import GraficoFluxo from '../components/GraficoFluxo.jsx'
import { hojeISO, limitesDoMes, mesDe, moeda, nomeDoMes, somarMeses } from '../formato.js'
import { useCarregar } from '../useCarregar.js'

export default function Painel() {
  // O período sai do relógio do navegador: o mês atual e os cinco seguintes.
  const mesAtual = mesDe(hojeISO())
  const de = limitesDoMes(mesAtual).de
  const ate = limitesDoMes(somarMeses(mesAtual, 5)).ate

  const { dados, erro, carregando } = useCarregar(async () => {
    const [fluxo, vencidas] = await Promise.all([
      api('/fluxo-caixa', { query: { de, ate } }),
      api('/contas', { query: { status: 'vencida' } }),
    ])

    return { fluxo, vencidas: vencidas.meta.total }
  }, mesAtual)

  if (carregando) return <p className="carregando">Carregando…</p>
  if (erro) return <Aviso>{erro}</Aviso>

  const { fluxo, vencidas } = dados
  const esteMes = fluxo.meses[0]

  return (
    <>
      <header className="cabecalho">
        <h1>{nomeDoMes(mesAtual)}</h1>
        <Link className="botao" to="/contas?nova=1">Nova conta</Link>
      </header>

      <section className="equacao" aria-label="Resumo do mês">
        <div>
          <span className="rotulo">A receber</span>
          <strong>{moeda(esteMes.receber)}</strong>
          <span className="detalhe">{moeda(esteMes.recebido)} já recebido</span>
        </div>
        <span className="operador" aria-hidden="true">−</span>
        <div>
          <span className="rotulo">A pagar</span>
          <strong>{moeda(esteMes.pagar)}</strong>
          <span className="detalhe">{moeda(esteMes.pago)} já pago</span>
        </div>
        <span className="operador" aria-hidden="true">=</span>
        <div>
          <span className="rotulo">Saldo previsto</span>
          <strong className={Number(esteMes.saldo) < 0 ? 'negativo' : ''}>{moeda(esteMes.saldo)}</strong>
          <span className="detalhe">se tudo for pago e recebido</span>
        </div>
      </section>

      {vencidas > 0 && (
        <p className="alerta">
          {vencidas === 1 ? 'Há 1 conta vencida em aberto.' : `Há ${vencidas} contas vencidas em aberto.`}{' '}
          <Link to="/contas?status=vencida&mes=todos">Ver contas vencidas</Link>
        </p>
      )}

      <section className="bloco">
        <h2>Fluxo de caixa em seis meses</h2>
        <GraficoFluxo meses={fluxo.meses} />

        <div className="tabela-rolagem">
          <table>
            <thead>
              <tr>
                <th>Mês</th>
                <th className="num">A receber</th>
                <th className="num">A pagar</th>
                <th className="num">Saldo do mês</th>
                <th className="num">Saldo acumulado</th>
              </tr>
            </thead>
            <tbody>
              {fluxo.meses.map((mes) => (
                <tr key={mes.mes}>
                  <td>{nomeDoMes(mes.mes)}</td>
                  <td className="num">{moeda(mes.receber)}</td>
                  <td className="num">{moeda(mes.pagar)}</td>
                  <td className={`num ${Number(mes.saldo) < 0 ? 'negativo' : ''}`}>{moeda(mes.saldo)}</td>
                  <td className={`num ${Number(mes.saldo_acumulado) < 0 ? 'negativo' : ''}`}>{moeda(mes.saldo_acumulado)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>
    </>
  )
}
