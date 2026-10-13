import { useState } from 'react'
import { Link } from 'react-router-dom'

import { api } from '../api.js'
import Aviso from '../components/Aviso.jsx'
import TelaDeEntrada from '../components/TelaDeEntrada.jsx'

export default function EsqueciSenha() {
  const [email, setEmail] = useState('')
  const [erro, setErro] = useState('')
  const [enviado, setEnviado] = useState('')
  const [enviando, setEnviando] = useState(false)

  async function enviar(evento) {
    evento.preventDefault()
    setErro('')
    setEnviando(true)

    try {
      const resposta = await api('/esqueci-senha', { metodo: 'POST', corpo: { email } })
      setEnviado(resposta.message)
    } catch (falha) {
      setErro(falha.erros.email?.[0] ?? falha.message)
    } finally {
      setEnviando(false)
    }
  }

  return (
    <TelaDeEntrada>
      <form className="entrada-form" onSubmit={enviar}>
        <h1>Esqueci minha senha</h1>
        <p className="entrada-sub">Informe o e-mail da sua conta. Vamos enviar um link para você criar uma nova senha.</p>

        <Aviso>{erro}</Aviso>

        {enviado ? (
          <p className="nota" role="status">{enviado}</p>
        ) : (
          <>
            <label>
              E-mail
              <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} autoComplete="email" required autoFocus />
            </label>

            <button type="submit" className="botao" disabled={enviando}>
              {enviando ? 'Enviando…' : 'Enviar link'}
            </button>
          </>
        )}

        <p className="entrada-troca">
          Lembrou a senha? <Link to="/login">Voltar para o login</Link>
        </p>
      </form>
    </TelaDeEntrada>
  )
}
