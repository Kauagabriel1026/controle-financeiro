import { useState } from 'react'
import { Link } from 'react-router-dom'

import { useAuth } from '../auth.jsx'
import Aviso from '../components/Aviso.jsx'
import { CAPTCHA_ATIVO } from '../captcha.js'
import Captcha from '../components/Captcha.jsx'
import TelaDeEntrada from '../components/TelaDeEntrada.jsx'

export default function Cadastro() {
  const { cadastrar } = useAuth()
  const [form, setForm] = useState({ name: '', email: '', password: '', password_confirmation: '' })
  const [erros, setErros] = useState({})
  const [erro, setErro] = useState('')
  const [enviando, setEnviando] = useState(false)
  const [captcha, setCaptcha] = useState('')
  const [chaveCaptcha, setChaveCaptcha] = useState(0)

  const mudar = (campo) => (evento) => setForm({ ...form, [campo]: evento.target.value })

  async function enviar(evento) {
    evento.preventDefault()
    setErro('')
    setErros({})
    setEnviando(true)

    try {
      await cadastrar({ ...form, captcha })
    } catch (falha) {
      setErros(falha.erros)
      if (falha.erros.captcha) setErro(falha.erros.captcha[0])
      else if (Object.keys(falha.erros).length === 0) setErro(falha.message)
      setEnviando(false)
      // Cada token do captcha vale para um único envio.
      setCaptcha('')
      setChaveCaptcha((chave) => chave + 1)
    }
  }

  return (
    <TelaDeEntrada>
      <form className="entrada-form" onSubmit={enviar}>
        <h1>Criar conta</h1>
        <p className="entrada-sub">Seus lançamentos ficam visíveis só para você.</p>

        <Aviso>{erro}</Aviso>

        <label>
          Nome
          <input value={form.name} onChange={mudar('name')} autoComplete="name" required autoFocus />
          {erros.name && <span className="erro-campo">{erros.name[0]}</span>}
        </label>

        <label>
          E-mail
          <input type="email" value={form.email} onChange={mudar('email')} autoComplete="email" required />
          {erros.email && <span className="erro-campo">{erros.email[0]}</span>}
        </label>

        <label>
          Senha
          <input type="password" value={form.password} onChange={mudar('password')} autoComplete="new-password" minLength={8} required />
          <span className="dica">Mínimo de 8 caracteres.</span>
          {erros.password && <span className="erro-campo">{erros.password[0]}</span>}
        </label>

        <label>
          Repita a senha
          <input type="password" value={form.password_confirmation} onChange={mudar('password_confirmation')} autoComplete="new-password" minLength={8} required />
        </label>

        <Captcha key={chaveCaptcha} aoMudar={setCaptcha} aoFalhar={setErro} />

        <button type="submit" className="botao" disabled={enviando || (CAPTCHA_ATIVO && !captcha)}>
          {enviando ? 'Criando…' : 'Criar conta'}
        </button>

        <p className="entrada-troca">
          Já tem conta? <Link to="/login">Entrar</Link>
        </p>
      </form>
    </TelaDeEntrada>
  )
}
