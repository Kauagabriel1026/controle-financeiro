import { NavLink, Outlet } from 'react-router-dom'

import { useAuth } from '../auth.jsx'

export default function Layout() {
  const { usuario, sair } = useAuth()

  return (
    <div className="app">
      <aside className="lateral">
        <p className="marca">Controle Financeiro</p>

        <nav aria-label="Principal">
          <NavLink to="/" end>Painel</NavLink>
          <NavLink to="/contas">Contas</NavLink>
          <NavLink to="/categorias">Categorias</NavLink>
        </nav>

        <div className="sessao">
          <p>{usuario.name}</p>
          <button type="button" className="botao-texto" onClick={sair}>Sair</button>
        </div>
      </aside>

      <main className="conteudo">
        <Outlet />
      </main>
    </div>
  )
}
