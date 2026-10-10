import { useState } from 'react'

import { api } from '../api.js'
import Aviso from '../components/Aviso.jsx'
import { useCarregar } from '../useCarregar.js'

export default function Categorias() {
  const { dados, erro, carregando, recarregar } = useCarregar(() => api('/categorias'))
  const [nome, setNome] = useState('')
  // Categoria que está sendo renomeada: { id, nome }.
  const [edicao, setEdicao] = useState(null)
  const [erroAcao, setErroAcao] = useState('')
  const [nota, setNota] = useState('')

  async function executar(acao) {
    setErroAcao('')
    setNota('')

    try {
      await acao()
      recarregar()

      return true
    } catch (falha) {
      setErroAcao(falha.erros.nome?.[0] ?? falha.message)

      return false
    }
  }

  async function criar(evento) {
    evento.preventDefault()

    if (await executar(() => api('/categorias', { metodo: 'POST', corpo: { nome } }))) {
      setNome('')
    }
  }

  async function renomear(evento) {
    evento.preventDefault()

    if (await executar(() => api(`/categorias/${edicao.id}`, { metodo: 'PUT', corpo: { nome: edicao.nome } }))) {
      setEdicao(null)
    }
  }

  async function adicionarSugeridas() {
    const antes = dados.data.length
    let depois = antes

    const deuCerto = await executar(async () => {
      depois = (await api('/categorias/sugeridas', { metodo: 'POST' })).data.length
    })

    if (deuCerto && depois === antes) {
      setNota('Você já tem todas as categorias sugeridas.')
    }
  }

  function excluir(categoria) {
    const pergunta = `Excluir a categoria "${categoria.nome}"? As contas dela continuam existindo, só ficam sem categoria.`

    if (window.confirm(pergunta)) {
      executar(() => api(`/categorias/${categoria.id}`, { metodo: 'DELETE' }))
    }
  }

  return (
    <>
      <header className="cabecalho">
        <h1>Categorias</h1>
        {dados && (
          <button type="button" className="botao secundario" onClick={adicionarSugeridas}>
            Adicionar categorias sugeridas
          </button>
        )}
      </header>

      <form className="nova-categoria" onSubmit={criar}>
        <label>
          Nova categoria
          <input value={nome} onChange={(e) => setNome(e.target.value)} maxLength={60} placeholder="Ex.: Aluguel" required />
        </label>
        <button type="submit" className="botao">Adicionar categoria</button>
      </form>

      <Aviso>{erro || erroAcao}</Aviso>
      {nota && <p className="nota" role="status">{nota}</p>}

      {carregando && !dados && <p className="carregando">Carregando…</p>}

      {dados && dados.data.length === 0 && (
        <p className="vazio">Nenhuma categoria ainda. Crie a primeira no campo acima ou adicione as sugeridas.</p>
      )}

      {dados && dados.data.length > 0 && (
        <ul className="lista-categorias">
          {dados.data.map((categoria) => (
            <li key={categoria.id}>
              {edicao?.id === categoria.id ? (
                <form onSubmit={renomear}>
                  <input value={edicao.nome} onChange={(e) => setEdicao({ ...edicao, nome: e.target.value })} maxLength={60} aria-label="Novo nome" required autoFocus />
                  <button type="submit" className="botao-texto forte">Salvar</button>
                  <button type="button" className="botao-texto" onClick={() => setEdicao(null)}>Cancelar</button>
                </form>
              ) : (
                <>
                  <span>{categoria.nome}</span>
                  <button type="button" className="botao-texto" onClick={() => setEdicao({ id: categoria.id, nome: categoria.nome })}>Renomear</button>
                  <button type="button" className="botao-texto perigo" onClick={() => excluir(categoria)}>Excluir</button>
                </>
              )}
            </li>
          ))}
        </ul>
      )}
    </>
  )
}
