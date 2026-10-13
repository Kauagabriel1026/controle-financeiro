import { useEffect, useRef, useState } from 'react'
import { Link } from 'react-router-dom'

import { useAuth } from '../auth.jsx'
import { iniciais, primeiroNome } from '../formato.js'
import { temaEfetivo } from '../tema.js'

/**
 * Botão com as iniciais e o nome de quem está logado. Abre um menu com o
 * perfil, a troca de tema e a saída. `compacto` mostra só as iniciais
 * (usado no cabeçalho do celular).
 */
export default function MenuUsuario({ compacto = false }) {
  const { usuario, sair, mudarTema } = useAuth()
  const [aberto, setAberto] = useState(false)
  const caixa = useRef(null)

  // Fecha ao clicar fora ou ao apertar Esc.
  useEffect(() => {
    if (!aberto) return undefined

    const clicarFora = (evento) => {
      if (!caixa.current?.contains(evento.target)) setAberto(false)
    }
    const apertarEsc = (evento) => {
      if (evento.key === 'Escape') setAberto(false)
    }

    document.addEventListener('mousedown', clicarFora)
    document.addEventListener('keydown', apertarEsc)

    return () => {
      document.removeEventListener('mousedown', clicarFora)
      document.removeEventListener('keydown', apertarEsc)
    }
  }, [aberto])

  const escuro = temaEfetivo() === 'escuro'

  function alternarTema() {
    setAberto(false)
    mudarTema(escuro ? 'claro' : 'escuro').catch(() => {})
  }

  return (
    <div className="menu-usuario" ref={caixa}>
      <button
        type="button"
        className="menu-usuario-botao"
        aria-haspopup="menu"
        aria-expanded={aberto}
        aria-label={compacto ? `Conta de ${usuario.name}` : undefined}
        onClick={() => setAberto(!aberto)}
      >
        <span className="avatar" aria-hidden="true">{iniciais(usuario.name)}</span>
        {!compacto && <span className="menu-usuario-ola">Olá, {primeiroNome(usuario.name)}</span>}
        <span className="menu-usuario-seta" aria-hidden="true" />
      </button>

      {aberto && (
        <div className="menu-usuario-lista" role="menu">
          <div className="menu-usuario-quem">
            <strong>{usuario.name}</strong>
            <span>{usuario.email}</span>
          </div>
          <Link role="menuitem" to="/perfil" onClick={() => setAberto(false)}>Meu perfil</Link>
          <button type="button" role="menuitem" onClick={alternarTema}>
            {escuro ? 'Usar tema claro' : 'Usar tema escuro'}
          </button>
          <button type="button" role="menuitem" className="menu-usuario-sair" onClick={sair}>Sair</button>
        </div>
      )}
    </div>
  )
}
