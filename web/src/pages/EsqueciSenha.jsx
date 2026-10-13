import { useState } from 'react'
import { Link, useLocation } from 'react-router-dom'

import { api } from '../api.js'
import Aviso from '../components/Aviso.jsx'
import { CAPTCHA_ATIVO } from '../captcha.js'
import Captcha from '../components/Captcha.jsx'
import TelaDeEntrada from '../components/TelaDeEntrada.jsx'

export default function EsqueciSenha() {
  // Vindo do login, o e-mail já digitado lá preenche o campo.
  const { state } = useLocation()
  const [email, setEmail] = useState(state?.email ?? '')
  const [erro, setErro] = useState('')
  const [enviado, setEnviado] = useState('')
  const [enviando, setEnviando] = useState(false)
  const [captcha, setCaptcha] = useState('')
  const [chaveCaptcha, setChaveCaptcha] = useState(0)

  async function enviar(evento) {
    evento.preventDefault()
    setErro('')
    setEnviando(true)

    try {
      const resposta = await api('/esqueci-senha', { metodo: 'POST', corpo: { email, captcha } })
      setEnviado(resposta.message)
    } catch (falha) {
      setErro(falha.erros.captcha?.[0] ?? falha.erros.email?.[0] ?? falha.message)
      setCaptcha('')
      setChaveCaptcha((chave) => chave + 1)
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

            <Captcha key={chaveCaptcha} aoMudar={setCaptcha} aoFalhar={setErro} />

            <button type="submit" className="botao" disabled={enviando || (CAPTCHA_ATIVO && !captcha)}>
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
