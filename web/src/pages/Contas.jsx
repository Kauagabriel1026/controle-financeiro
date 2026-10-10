import { useState } from 'react'
import { useSearchParams } from 'react-router-dom'

import { api } from '../api.js'
import Aviso from '../components/Aviso.jsx'
import ContaForm from '../components/ContaForm.jsx'
import { dataBR, hojeISO, limitesDoMes, mesDe, moeda, nomeDoMes, somarMeses } from '../formato.js'
import { useCarregar } from '../useCarregar.js'

const NOME_STATUS = { pendente: 'Pendente', paga: 'Paga', vencida: 'Vencida' }

export default function Contas() {
  // Os filtros ficam no endereço da página, então dá para compartilhar o link
  // e o botão "voltar" do navegador funciona como esperado.
  const [parametros, setParametros] = useSearchParams()
  const mes = parametros.get('mes') ?? mesDe(hojeISO())
  const tipo = parametros.get('tipo') ?? ''
  const status = parametros.get('status') ?? ''
  const pagina = parametros.get('pagina') ?? '1'

  // `null` = fechado, `{}` = nova conta, objeto com dados = edição.
  const [emEdicao, setEmEdicao] = useState(parametros.get('nova') ? {} : null)
  const [erroAcao, setErroAcao] = useState('')

  const periodo = mes === 'todos' ? {} : limitesDoMes(mes)

  const { dados, erro, carregando, recarregar } = useCarregar(async () => {
    const [contas, categorias] = await Promise.all([
      api('/contas', { query: { ...periodo, tipo, status, page: pagina } }),
      api('/categorias'),
    ])

    return { contas: contas.data, meta: contas.meta, categorias: categorias.data }
  }, `${mes}|${tipo}|${status}|${pagina}`)

  function filtrar(mudancas) {
    const novos = new URLSearchParams(parametros)

    novos.delete('pagina')
    novos.delete('nova')

    for (const [chave, valor] of Object.entries(mudancas)) {
      if (valor) novos.set(chave, valor)
      else novos.delete(chave)
    }

    setParametros(novos)
  }

  async function executar(acao) {
    setErroAcao('')

    try {
      await acao()
      recarregar()
    } catch (falha) {
      setErroAcao(falha.message)
    }
  }

  const darBaixa = (conta) => executar(() => api(`/contas/${conta.id}/pagar`, { metodo: 'POST' }))
  const estornar = (conta) => executar(() => api(`/contas/${conta.id}/estornar`, { metodo: 'POST' }))

  function excluir(conta) {
    if (!window.confirm(`Excluir a conta "${conta.descricao}"? Essa ação não pode ser desfeita.`)) return

    executar(() => api(`/contas/${conta.id}`, { metodo: 'DELETE' }))
  }

  function fecharJanela() {
    setEmEdicao(null)
    if (parametros.get('nova')) filtrar({})
  }

  return (
    <>
      <header className="cabecalho">
        <h1>Contas</h1>
        <button type="button" className="botao" onClick={() => setEmEdicao({})}>Nova conta</button>
      </header>

      <div className="filtros">
        {mes === 'todos' ? (
          <div className="passo-mes">
            <strong>Todos os meses</strong>
            <button type="button" className="botao-texto" onClick={() => filtrar({ mes: '' })}>Voltar para o mês atual</button>
          </div>
        ) : (
          <div className="passo-mes">
            <button type="button" className="botao-icone" aria-label="Mês anterior" onClick={() => filtrar({ mes: somarMeses(mes, -1) })}>‹</button>
            <strong>{nomeDoMes(mes)}</strong>
            <button type="button" className="botao-icone" aria-label="Próximo mês" onClick={() => filtrar({ mes: somarMeses(mes, 1) })}>›</button>
            <button type="button" className="botao-texto" onClick={() => filtrar({ mes: 'todos' })}>Ver todos os meses</button>
          </div>
        )}

        <label>
          Tipo
          <select value={tipo} onChange={(e) => filtrar({ tipo: e.target.value })}>
            <option value="">A pagar e a receber</option>
            <option value="pagar">A pagar</option>
            <option value="receber">A receber</option>
          </select>
        </label>

        <label>
          Situação
          <select value={status} onChange={(e) => filtrar({ status: e.target.value })}>
            <option value="">Todas</option>
            <option value="pendente">Pendentes</option>
            <option value="vencida">Vencidas</option>
            <option value="paga">Pagas</option>
          </select>
        </label>
      </div>

      <Aviso>{erro || erroAcao}</Aviso>

      {carregando && !dados && <p className="carregando">Carregando…</p>}

      {dados && dados.contas.length === 0 && (
        <p className="vazio">
          Nenhuma conta com esses filtros.{' '}
          <button type="button" className="botao-texto" onClick={() => setEmEdicao({})}>Cadastrar uma conta</button>
        </p>
      )}

      {dados && dados.contas.length > 0 && (
        <div className="tabela-rolagem">
          <table>
            <thead>
              <tr>
                <th>Vencimento</th>
                <th>Descrição</th>
                <th>Tipo</th>
                <th className="num">Valor</th>
                <th>Situação</th>
                <th><span className="so-leitor">Ações</span></th>
              </tr>
            </thead>
            <tbody>
              {dados.contas.map((conta) => (
                <tr key={conta.id}>
                  <td>{dataBR(conta.vencimento)}</td>
                  <td>
                    {conta.descricao}
                    {conta.categoria && <span className="categoria">{conta.categoria.nome}</span>}
                  </td>
                  <td>
                    <span className={`tipo ${conta.tipo}`}>{conta.tipo === 'pagar' ? 'A pagar' : 'A receber'}</span>
                  </td>
                  <td className="num">{moeda(conta.valor)}</td>
                  <td>
                    <span className={`situacao ${conta.status}`}>{NOME_STATUS[conta.status]}</span>
                    {conta.pago_em && <span className="categoria">em {dataBR(conta.pago_em)}</span>}
                  </td>
                  <td>
                    <div className="acoes">
                      {conta.status === 'paga' ? (
                        <button type="button" className="botao-texto" onClick={() => estornar(conta)}>Estornar</button>
                      ) : (
                        <button type="button" className="botao-texto forte" onClick={() => darBaixa(conta)}>Dar baixa</button>
                      )}
                      <button type="button" className="botao-texto" onClick={() => setEmEdicao(conta)}>Editar</button>
                      <button type="button" className="botao-texto perigo" onClick={() => excluir(conta)}>Excluir</button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {dados && dados.meta.last_page > 1 && (
        <nav className="paginacao" aria-label="Páginas">
          <button type="button" className="botao secundario" disabled={dados.meta.current_page === 1} onClick={() => filtrar({ pagina: String(dados.meta.current_page - 1) })}>Anterior</button>
          <span>Página {dados.meta.current_page} de {dados.meta.last_page}</span>
          <button type="button" className="botao secundario" disabled={dados.meta.current_page === dados.meta.last_page} onClick={() => filtrar({ pagina: String(dados.meta.current_page + 1) })}>Próxima</button>
        </nav>
      )}

      {emEdicao && dados && (
        <ContaForm
          conta={emEdicao.id ? emEdicao : null}
          categorias={dados.categorias}
          aoSalvar={() => {
            fecharJanela()
            recarregar()
          }}
          aoFechar={fecharJanela}
        />
      )}
    </>
  )
}
