import { useState } from 'react'
import { Link } from 'react-router-dom'

import { useAuth } from '../auth.jsx'
import Aviso from '../components/Aviso.jsx'

export default function Login() {
  const { entrar } = useAuth()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [erro, setErro] = useState('')
  const [enviando, setEnviando] = useState(false)

  async function enviar(evento) {
    evento.preventDefault()
    setErro('')
    setEnviando(true)

    try {
      await entrar({ email, password })
    } catch (falha) {
      setErro(falha.erros.email?.[0] ?? falha.message)
      setEnviando(false)
    }
  }

  return (
    <div className="entrada">
      <form className="entrada-form" onSubmit={enviar}>
        <h1>Controle Financeiro</h1>
        <p className="entrada-sub">Entre para ver suas contas a pagar e a receber.</p>

        <Aviso>{erro}</Aviso>

        <label>
          E-mail
          <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} autoComplete="email" required autoFocus />
        </label>

        <label>
          Senha
          <input type="password" value={password} onChange={(e) => setPassword(e.target.value)} autoComplete="current-password" required />
        </label>

        <button type="submit" className="botao" disabled={enviando}>
          {enviando ? 'Entrando…' : 'Entrar'}
        </button>

        <p className="entrada-troca">
          Ainda não tem conta? <Link to="/cadastro">Criar conta</Link>
        </p>
      </form>
    </div>
  )
}
