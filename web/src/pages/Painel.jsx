import { useState } from 'react'
import { Link } from 'react-router-dom'

import { api } from '../api.js'
import Aviso from '../components/Aviso.jsx'
import GraficoFluxo from '../components/GraficoFluxo.jsx'
import Indicador, { Medidor } from '../components/Indicador.jsx'
import ProximasContas from '../components/ProximasContas.jsx'
import TotaisPorCategoria from '../components/TotaisPorCategoria.jsx'
import { hojeISO, limitesDoMes, mesDe, moeda, nomeDoMes, somarMeses } from '../formato.js'
import { useCarregar } from '../useCarregar.js'

export default function Painel() {
  const mesAtual = mesDe(hojeISO())
  const [mes, setMes] = useState(mesAtual)

  // O fluxo de caixa mostra o mês escolhido e os cinco seguintes.
  const de = limitesDoMes(mes).de
  const ate = limitesDoMes(somarMeses(mes, 5)).ate

  const { dados, erro, carregando } = useCarregar(async () => {
    const [fluxo, vencidas, pendentes, porCategoria] = await Promise.all([
      api('/fluxo-caixa', { query: { de, ate } }),
      api('/contas', { query: { status: 'vencida' } }),
      api('/contas', { query: { status: 'pendente' } }),
      api('/totais-por-categoria', { query: limitesDoMes(mes) }),
    ])

    return {
      fluxo,
      vencidas: vencidas.meta.total,
      // A lista já vem por vencimento; as cinco primeiras são as mais próximas.
      proximas: pendentes.data.slice(0, 5),
      porCategoria,
    }
  }, mes)

  if (carregando && !dados) return <p className="carregando">Carregando…</p>
  if (erro) return <Aviso>{erro}</Aviso>

  const { fluxo, vencidas, proximas, porCategoria } = dados
  const doMes = fluxo.meses[0]

  return (
    <>
      <header className="cabecalho">
        <div className="troca-mes">
          <button type="button" className="botao-icone" aria-label="Mês anterior" onClick={() => setMes(somarMeses(mes, -1))}>‹</button>
          <h1>{nomeDoMes(mes)}</h1>
          <button type="button" className="botao-icone" aria-label="Próximo mês" onClick={() => setMes(somarMeses(mes, 1))}>›</button>
          {mes !== mesAtual && (
            <button type="button" className="botao-texto" onClick={() => setMes(mesAtual)}>Voltar para o mês atual</button>
          )}
        </div>
        <Link className="botao" to="/contas?nova=1">Nova conta</Link>
      </header>

      <section className="indicadores" aria-label="Resumo do mês">
        <Indicador rotulo="A receber" valor={moeda(doMes.receber)} tom="receber">
          <Medidor parte={doMes.recebido} total={doMes.receber} tipo="receber" legenda={`${moeda(doMes.recebido)} já recebido`} />
        </Indicador>

        <Indicador rotulo="A pagar" valor={moeda(doMes.pagar)} tom="pagar">
          <Medidor parte={doMes.pago} total={doMes.pagar} tipo="pagar" legenda={`${moeda(doMes.pago)} já pago`} />
        </Indicador>

        <Indicador rotulo="Saldo previsto" valor={moeda(doMes.saldo)} tom={Number(doMes.saldo) < 0 ? 'negativo' : 'saldo'}>
          <span className="detalhe">Se tudo for pago e recebido</span>
        </Indicador>

        <Indicador rotulo="Contas vencidas" valor={String(vencidas)} tom={vencidas > 0 ? 'negativo' : 'neutro'}>
          {vencidas > 0 ? (
            <Link className="detalhe" to="/contas?status=vencida&mes=todos">Ver contas vencidas</Link>
          ) : (
            <span className="detalhe">Tudo em dia</span>
          )}
        </Indicador>
      </section>

      <div className="grade-painel">
        <section className="cartao">
          <h2>Fluxo de caixa em seis meses</h2>
          <GraficoFluxo meses={fluxo.meses} />
        </section>

        <section className="cartao">
          <h2>Próximas contas</h2>
          <ProximasContas contas={proximas} />
        </section>

        <section className="cartao">
          <h2>Gastos do mês por categoria</h2>
          <TotaisPorCategoria totais={porCategoria.gastos} tipo="pagar" />
        </section>

        <section className="cartao">
          <h2>Ganhos do mês por categoria</h2>
          <TotaisPorCategoria totais={porCategoria.ganhos} tipo="receber" />
        </section>
      </div>

      <section className="bloco">
        <h2>Mês a mês</h2>
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
              {fluxo.meses.map((linha) => (
                <tr key={linha.mes}>
                  <td>{nomeDoMes(linha.mes)}</td>
                  <td className="num">{moeda(linha.receber)}</td>
                  <td className="num">{moeda(linha.pagar)}</td>
                  <td className={`num ${Number(linha.saldo) < 0 ? 'negativo' : ''}`}>{moeda(linha.saldo)}</td>
                  <td className={`num ${Number(linha.saldo_acumulado) < 0 ? 'negativo' : ''}`}>{moeda(linha.saldo_acumulado)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>
    </>
  )
}
