import { useState } from 'react'
import { Link } from 'react-router-dom'

import { useAuth } from '../auth.jsx'
import Aviso from '../components/Aviso.jsx'
import TelaDeEntrada from '../components/TelaDeEntrada.jsx'

export default function Cadastro() {
  const { cadastrar } = useAuth()
  const [form, setForm] = useState({ name: '', email: '', password: '', password_confirmation: '' })
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
      await cadastrar(form)
    } catch (falha) {
      setErros(falha.erros)
      if (Object.keys(falha.erros).length === 0) setErro(falha.message)
      setEnviando(false)
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

        <button type="submit" className="botao" disabled={enviando}>
          {enviando ? 'Criando…' : 'Criar conta'}
        </button>

        <p className="entrada-troca">
          Já tem conta? <Link to="/login">Entrar</Link>
        </p>
      </form>
    </TelaDeEntrada>
  )
}
