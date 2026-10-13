import { NavLink, Outlet } from 'react-router-dom'

import { useAuth } from '../auth.jsx'
import Marca from './Marca.jsx'
import MenuUsuario from './MenuUsuario.jsx'

export default function Layout() {
  const { usuario } = useAuth()

  return (
    <div className="app">
      <aside className="lateral">
        <Marca />

        <nav aria-label="Principal">
          <NavLink to="/" end>Painel</NavLink>
          <NavLink to="/contas">Contas</NavLink>
          <NavLink to="/categorias">Categorias</NavLink>
        </nav>

        {/* No celular a lateral vira cabeçalho, e o menu da conta fica nele. */}
        <div className="conta-celular">
          <MenuUsuario compacto />
        </div>
      </aside>

      <main className="conteudo">
        <div className="topo">
          <MenuUsuario />
        </div>

        {usuario.demonstracao && (
          <p className="faixa-demonstracao" role="note">
            Você está em uma conta de demonstração. Os dados são de exemplo e ficam guardados por 24 horas.
          </p>
        )}
        <Outlet />
      </main>
    </div>
  )
}
