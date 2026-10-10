import { useEffect, useRef, useState } from 'react'

import { api } from '../api.js'
import { centavosParaValor, hojeISO, valorParaCentavos } from '../formato.js'
import Aviso from './Aviso.jsx'
import CampoValor from './CampoValor.jsx'

/**
 * Janela de criar ou editar conta. Recebe `conta` para editar; sem ela, cria.
 * O <dialog> nativo já cuida de fechar com Esc e de prender o foco na janela.
 */
export default function ContaForm({ conta, categorias, aoSalvar, aoFechar }) {
  const janela = useRef(null)
  const [form, setForm] = useState({
    tipo: conta?.tipo ?? 'pagar',
    descricao: conta?.descricao ?? '',
    centavos: conta ? valorParaCentavos(conta.valor) : 0,
    vencimento: conta?.vencimento ?? hojeISO(),
    categoria_id: conta?.categoria?.id ?? '',
    repetir_meses: '1',
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

    if (form.centavos <= 0) {
      setErros({ valor: ['Informe um valor maior que zero.'] })

      return
    }

    setEnviando(true)

    const corpo = {
      tipo: form.tipo,
      descricao: form.descricao,
      valor: centavosParaValor(form.centavos),
      vencimento: form.vencimento,
      // O <select> devolve texto; a API espera o número da categoria ou nulo.
      categoria_id: form.categoria_id ? Number(form.categoria_id) : null,
    }

    // A repetição só existe ao criar; ao editar, só esta conta muda.
    if (!conta) corpo.repetir_meses = Number(form.repetir_meses)

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
            <CampoValor centavos={form.centavos} aoMudar={(centavos) => setForm({ ...form, centavos })} />
            <span className="dica">Digite só os números: 160000 vira 1.600,00.</span>
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

        {!conta && (
          <label>
            Repetir
            <select value={form.repetir_meses} onChange={mudar('repetir_meses')}>
              <option value="1">Não repetir</option>
              <option value="3">Todo mês, por 3 meses</option>
              <option value="6">Todo mês, por 6 meses</option>
              <option value="12">Todo mês, por 12 meses</option>
              <option value="24">Todo mês, por 24 meses</option>
            </select>
            <span className="dica">Cria uma conta igual em cada mês, no mesmo dia do vencimento.</span>
            {erros.repetir_meses && <span className="erro-campo">{erros.repetir_meses[0]}</span>}
          </label>
        )}

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
