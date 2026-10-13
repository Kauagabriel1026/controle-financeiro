import { useState } from 'react'

import { api } from '../api.js'
import { useAuth } from '../auth.jsx'
import Aviso from '../components/Aviso.jsx'
import CampoValor from '../components/CampoValor.jsx'
import { centavosParaValor, valorParaCentavos } from '../formato.js'

const OBJETIVOS = [
  ['sair_das_dividas', 'Sair das dívidas'],
  ['montar_reserva', 'Montar uma reserva de emergência'],
  ['controlar_gastos', 'Controlar melhor os gastos'],
  ['investir', 'Começar a investir'],
]

const TEMAS = [
  ['claro', 'Claro'],
  ['escuro', 'Escuro'],
  ['sistema', 'Automático', 'Segue o tema do seu aparelho'],
]

/** Envia uma parte do perfil e cuida do estado de envio, do erro e da confirmação. */
function useSalvar(aoSalvar) {
  const [estado, setEstado] = useState({ enviando: false, erro: '', erros: {}, salvo: '' })

  async function salvar(caminho, corpo, mensagem = 'Alterações salvas.') {
    setEstado({ enviando: true, erro: '', erros: {}, salvo: '' })

    try {
      const resposta = await api(caminho, { metodo: 'PUT', corpo })
      aoSalvar?.(resposta)
      setEstado({ enviando: false, erro: '', erros: {}, salvo: resposta?.message ?? mensagem })

      return true
    } catch (falha) {
      const temErrosDeCampo = Object.keys(falha.erros).length > 0
      setEstado({ enviando: false, erro: temErrosDeCampo ? '' : falha.message, erros: falha.erros, salvo: '' })

      return false
    }
  }

  return [estado, salvar]
}

function Confirmacao({ children }) {
  return children ? <p className="salvo" role="status">{children}</p> : null
}

function ErroDoCampo({ erros, campo }) {
  return erros[campo] ? <span className="erro-campo">{erros[campo][0]}</span> : null
}

function DadosDaConta() {
  const { usuario, atualizarUsuario } = useAuth()
  const [form, setForm] = useState({ name: usuario.name, email: usuario.email })
  const [estado, salvar] = useSalvar(atualizarUsuario)

  return (
    <form className="cartao perfil-parte" onSubmit={(e) => { e.preventDefault(); salvar('/perfil', form) }}>
      <h2>Dados da conta</h2>
      <Aviso>{estado.erro}</Aviso>

      <label>
        Nome
        <input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} autoComplete="name" required />
        <ErroDoCampo erros={estado.erros} campo="name" />
      </label>

      <label>
        E-mail
        <input type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} autoComplete="email" required />
        <ErroDoCampo erros={estado.erros} campo="email" />
      </label>

      <div className="perfil-acoes">
        <button type="submit" className="botao" disabled={estado.enviando}>{estado.enviando ? 'Salvando…' : 'Salvar dados'}</button>
        <Confirmacao>{estado.salvo}</Confirmacao>
      </div>
    </form>
  )
}

function PerfilFinanceiro() {
  const { usuario, atualizarUsuario } = useAuth()
  const centavos = (valor) => (valor === null || valor === undefined ? 0 : valorParaCentavos(valor))
  const [form, setForm] = useState({
    renda: centavos(usuario.renda_mensal),
    dia: usuario.dia_pagamento ?? '',
    objetivo: usuario.objetivo ?? '',
    meta: centavos(usuario.meta_economia),
  })
  const [estado, salvar] = useSalvar(atualizarUsuario)

  function enviar(evento) {
    evento.preventDefault()
    salvar('/perfil', {
      renda_mensal: form.renda > 0 ? centavosParaValor(form.renda) : null,
      dia_pagamento: form.dia === '' ? null : Number(form.dia),
      objetivo: form.objetivo || null,
      meta_economia: form.meta > 0 ? centavosParaValor(form.meta) : null,
    })
  }

  return (
    <form className="cartao perfil-parte" onSubmit={enviar}>
      <h2>Perfil financeiro</h2>
      <p className="perfil-explica">
        Opcional. Essas informações vão ajudar o Kamel a dar dicas feitas para você. Ficam visíveis só para você.
      </p>
      <Aviso>{estado.erro}</Aviso>

      <div className="perfil-dupla">
        <label>
          Renda mensal aproximada (R$)
          <CampoValor centavos={form.renda} aoMudar={(renda) => setForm({ ...form, renda })} />
          <ErroDoCampo erros={estado.erros} campo="renda_mensal" />
        </label>

        <label>
          Dia em que você recebe
          <input type="number" min="1" max="31" inputMode="numeric" value={form.dia} onChange={(e) => setForm({ ...form, dia: e.target.value })} />
          <ErroDoCampo erros={estado.erros} campo="dia_pagamento" />
        </label>
      </div>

      <label>
        Objetivo principal
        <select value={form.objetivo} onChange={(e) => setForm({ ...form, objetivo: e.target.value })}>
          <option value="">Escolha um objetivo</option>
          {OBJETIVOS.map(([valor, texto]) => <option key={valor} value={valor}>{texto}</option>)}
        </select>
        <ErroDoCampo erros={estado.erros} campo="objetivo" />
      </label>

      <label>
        Quanto quer guardar por mês (R$)
        <CampoValor centavos={form.meta} aoMudar={(meta) => setForm({ ...form, meta })} />
        <ErroDoCampo erros={estado.erros} campo="meta_economia" />
      </label>

      <div className="perfil-acoes">
        <button type="submit" className="botao" disabled={estado.enviando}>{estado.enviando ? 'Salvando…' : 'Salvar perfil financeiro'}</button>
        <Confirmacao>{estado.salvo}</Confirmacao>
      </div>
    </form>
  )
}

function Aparencia() {
  const { usuario, mudarTema } = useAuth()
  const [erro, setErro] = useState('')

  function escolher(tema) {
    setErro('')
    mudarTema(tema).catch(() => setErro('Não foi possível salvar o tema na sua conta. Ele vale só neste aparelho por enquanto.'))
  }

  return (
    <section className="cartao perfil-parte">
      <h2>Aparência</h2>
      <Aviso>{erro}</Aviso>

      <fieldset className="temas">
        <legend>Tema</legend>
        {TEMAS.map(([valor, nome, dica]) => (
          <label key={valor} className={`tema-opcao ${valor}`}>
            <input type="radio" name="tema" value={valor} checked={usuario.tema === valor} onChange={() => escolher(valor)} />
            <span className="tema-amostra" aria-hidden="true"><span /><span /></span>
            <span className="tema-nome">{nome}</span>
            {dica && <span className="dica">{dica}</span>}
          </label>
        ))}
      </fieldset>
    </section>
  )
}

function Seguranca() {
  const { usuario } = useAuth()
  const vazio = { senha_atual: '', password: '', password_confirmation: '' }
  const [form, setForm] = useState(vazio)
  const [estado, salvar] = useSalvar()

  if (usuario.demonstracao) {
    return (
      <section className="cartao perfil-parte">
        <h2>Senha</h2>
        <p className="perfil-explica">A conta de demonstração não tem senha. Crie sua própria conta para usar esta opção.</p>
      </section>
    )
  }

  async function enviar(evento) {
    evento.preventDefault()
    if (await salvar('/perfil/senha', form)) setForm(vazio)
  }

  const mudar = (campo) => (e) => setForm({ ...form, [campo]: e.target.value })

  return (
    <form className="cartao perfil-parte" onSubmit={enviar}>
      <h2>Senha</h2>
      <Aviso>{estado.erro}</Aviso>

      <label>
        Senha atual
        <input type="password" value={form.senha_atual} onChange={mudar('senha_atual')} autoComplete="current-password" required />
        <ErroDoCampo erros={estado.erros} campo="senha_atual" />
      </label>

      <div className="perfil-dupla">
        <label>
          Nova senha
          <input type="password" value={form.password} onChange={mudar('password')} autoComplete="new-password" minLength={8} required />
          <ErroDoCampo erros={estado.erros} campo="password" />
        </label>

        <label>
          Repita a nova senha
          <input type="password" value={form.password_confirmation} onChange={mudar('password_confirmation')} autoComplete="new-password" minLength={8} required />
        </label>
      </div>

      <div className="perfil-acoes">
        <button type="submit" className="botao" disabled={estado.enviando}>{estado.enviando ? 'Trocando…' : 'Trocar senha'}</button>
        <Confirmacao>{estado.salvo}</Confirmacao>
      </div>
    </form>
  )
}

export default function Perfil() {
  return (
    <>
      <header className="cabecalho">
        <h1>Meu perfil</h1>
      </header>

      <div className="perfil">
        <DadosDaConta />
        <PerfilFinanceiro />
        <Aparencia />
        <Seguranca />
      </div>
    </>
  )
}
