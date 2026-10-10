import { useEffect, useRef, useState } from 'react'

import { api } from '../api.js'
import { hojeISO } from '../formato.js'
import Aviso from './Aviso.jsx'

/**
 * Janela de criar ou editar conta. Recebe `conta` para editar; sem ela, cria.
 * O <dialog> nativo já cuida de fechar com Esc e de prender o foco na janela.
 */
export default function ContaForm({ conta, categorias, aoSalvar, aoFechar }) {
  const janela = useRef(null)
  const [form, setForm] = useState({
    tipo: conta?.tipo ?? 'pagar',
    descricao: conta?.descricao ?? '',
    valor: conta?.valor ?? '',
    vencimento: conta?.vencimento ?? hojeISO(),
    categoria_id: conta?.categoria?.id ?? '',
  })
  const [erros, setErros] = useState({})
  const [erro, setErro] = useState('')
  const [enviando, setEnviando] = useState(false)

  useEffect(() => {
    janela.current.showModal()
  }, [])

  const mudar = (campo) => (evento) => setForm({ ...form, [campo]: evento.target.value })

  async function enviar(evento) {
    evento.preventDefault()
    setErro('')
    setErros({})
    setEnviando(true)

    // O <select> devolve texto; a API espera o número da categoria ou nulo.
    const corpo = { ...form, categoria_id: form.categoria_id ? Number(form.categoria_id) : null }

    try {
      if (conta) {
        await api(`/contas/${conta.id}`, { metodo: 'PUT', corpo })
      } else {
        await api('/contas', { metodo: 'POST', corpo })
      }

      aoSalvar()
    } catch (falha) {
      setErros(falha.erros)
      if (Object.keys(falha.erros).length === 0) setErro(falha.message)
      setEnviando(false)
    }
  }

  return (
    <dialog ref={janela} className="janela" onClose={aoFechar}>
      <form onSubmit={enviar}>
        <h2>{conta ? 'Editar conta' : 'Nova conta'}</h2>

        <Aviso>{erro}</Aviso>

        <fieldset className="alternar">
          <legend>Tipo</legend>
          <label>
            <input type="radio" name="tipo" value="pagar" checked={form.tipo === 'pagar'} onChange={mudar('tipo')} />
            A pagar
          </label>
          <label>
            <input type="radio" name="tipo" value="receber" checked={form.tipo === 'receber'} onChange={mudar('tipo')} />
            A receber
          </label>
        </fieldset>

        <label>
          Descrição
          <input value={form.descricao} onChange={mudar('descricao')} maxLength={120} required autoFocus />
          {erros.descricao && <span className="erro-campo">{erros.descricao[0]}</span>}
        </label>

        <div className="dupla">
          <label>
            Valor (R$)
            <input type="number" value={form.valor} onChange={mudar('valor')} min="0.01" step="0.01" required />
            {erros.valor && <span className="erro-campo">{erros.valor[0]}</span>}
          </label>

          <label>
            Vencimento
            <input type="date" value={form.vencimento} onChange={mudar('vencimento')} required />
            {erros.vencimento && <span className="erro-campo">{erros.vencimento[0]}</span>}
          </label>
        </div>

        <label>
          Categoria
          <select value={form.categoria_id} onChange={mudar('categoria_id')}>
            <option value="">Sem categoria</option>
            {categorias.map((categoria) => (
              <option key={categoria.id} value={categoria.id}>{categoria.nome}</option>
            ))}
          </select>
          {erros.categoria_id && <span className="erro-campo">{erros.categoria_id[0]}</span>}
        </label>

        <div className="acoes-janela">
          <button type="button" className="botao secundario" onClick={() => janela.current.close()}>Cancelar</button>
          <button type="submit" className="botao" disabled={enviando}>
            {enviando ? 'Salvando…' : 'Salvar conta'}
          </button>
        </div>
      </form>
    </dialog>
  )
}
