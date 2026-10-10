import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'

import { useAuth } from '../auth.jsx'
import Aviso from '../components/Aviso.jsx'
import TelaDeEntrada from '../components/TelaDeEntrada.jsx'

export default function Login() {
  const { entrar, demonstrar } = useAuth()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [erro, setErro] = useState('')
  const [enviando, setEnviando] = useState(false)
  const [demorando, setDemorando] = useState(false)

  // A hospedagem gratuita desliga a API quando ninguém usa; a primeira chamada
  // depois disso leva até um minuto. O aviso evita parecer que travou.
  useEffect(() => {
    if (!enviando) return undefined

    const espera = setTimeout(() => setDemorando(true), 4000)

    return () => {
      clearTimeout(espera)
      setDemorando(false)
    }
  }, [enviando])

  async function enviar(evento) {
    evento.preventDefault()
    setErro('')
    setEnviando('login')

    try {
      await entrar({ email, password })
    } catch (falha) {
      setErro(falha.erros.email?.[0] ?? falha.message)
      setEnviando(false)
    }
  }

  async function verDemonstracao() {
    setErro('')
    setEnviando('demonstracao')

    try {
      await demonstrar()
    } catch (falha) {
      setErro(falha.message)
      setEnviando(false)
    }
  }

  return (
    <TelaDeEntrada>
      <form className="entrada-form" onSubmit={enviar}>
        <h1>Entrar</h1>
        <p className="entrada-sub">Acesse suas contas a pagar e a receber.</p>

        <Aviso>{erro}</Aviso>

        <label>
          E-mail
          <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} autoComplete="email" required autoFocus />
        </label>

        <label>
          Senha
          <input type="password" value={password} onChange={(e) => setPassword(e.target.value)} autoComplete="current-password" required />
        </label>

        <button type="submit" className="botao" disabled={Boolean(enviando)}>
          {enviando === 'login' ? 'Entrando…' : 'Entrar'}
        </button>

        <button type="button" className="botao secundario" disabled={Boolean(enviando)} onClick={verDemonstracao}>
          {enviando === 'demonstracao' ? 'Preparando a demonstração…' : 'Ver demonstração'}
        </button>
        <p className="entrada-dica">Abre uma conta de visitante com dados de exemplo, sem cadastro.</p>

        {demorando && (
          <p className="entrada-dica" role="status">O servidor estava em repouso e está ligando. Isso leva até um minuto.</p>
        )}

        <p className="entrada-troca">
          Ainda não tem conta? <Link to="/cadastro">Criar conta</Link>
        </p>
      </form>
    </TelaDeEntrada>
  )
}
