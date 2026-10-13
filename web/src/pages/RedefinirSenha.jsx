import { useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'

import { api } from '../api.js'
import Aviso from '../components/Aviso.jsx'
import TelaDeEntrada from '../components/TelaDeEntrada.jsx'

export default function RedefinirSenha() {
  const [parametros] = useSearchParams()
  const navegar = useNavigate()
  const token = parametros.get('token') ?? ''
  const email = parametros.get('email') ?? ''

  const [form, setForm] = useState({ password: '', password_confirmation: '' })
  const [erros, setErros] = useState({})
  const [erro, setErro] = useState('')
  const [enviando, setEnviando] = useState(false)

  const mudar = (campo) => (evento) => setForm({ ...form, [campo]: evento.target.value })

  async function enviar(evento) {
    evento.preventDefault()
    setErro('')
    setErros({})
    setEnviando(true)

    try {
      const resposta = await api('/redefinir-senha', { metodo: 'POST', corpo: { ...form, token, email } })
      navegar('/login', { replace: true, state: { aviso: resposta.message, email } })
    } catch (falha) {
      setErros(falha.erros)
      setErro(falha.erros.token?.[0] ?? falha.erros.email?.[0] ?? (Object.keys(falha.erros).length ? '' : falha.message))
      setEnviando(false)
    }
  }

  const linkInvalido = !token || !email || erros.token

  return (
    <TelaDeEntrada>
      <form className="entrada-form" onSubmit={enviar}>
        <h1>Criar nova senha</h1>

        {!token || !email ? (
          <Aviso>Este link está incompleto. Abra o link do e-mail de novo ou peça um novo.</Aviso>
        ) : (
          <>
            <p className="entrada-sub">Conta: <strong>{email}</strong></p>

            <Aviso>{erro}</Aviso>

            <label>
              Nova senha
              <input type="password" value={form.password} onChange={mudar('password')} autoComplete="new-password" minLength={8} required autoFocus />
              <span className="dica">Mínimo de 8 caracteres.</span>
              {erros.password && <span className="erro-campo">{erros.password[0]}</span>}
            </label>

            <label>
              Repita a nova senha
              <input type="password" value={form.password_confirmation} onChange={mudar('password_confirmation')} autoComplete="new-password" minLength={8} required />
            </label>

            <button type="submit" className="botao" disabled={enviando}>
              {enviando ? 'Salvando…' : 'Salvar nova senha'}
            </button>
          </>
        )}

        <p className="entrada-troca">
          {linkInvalido ? <Link to="/esqueci-senha">Pedir um novo link</Link> : <Link to="/login">Voltar para o login</Link>}
        </p>
      </form>
    </TelaDeEntrada>
  )
}
