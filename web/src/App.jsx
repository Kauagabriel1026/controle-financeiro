import { Navigate, Route, Routes } from 'react-router-dom'

import { useAuth } from './auth.jsx'
import Aviso from './components/Aviso.jsx'
import Layout from './components/Layout.jsx'
import Cadastro from './pages/Cadastro.jsx'
import Categorias from './pages/Categorias.jsx'
import Contas from './pages/Contas.jsx'
import EsqueciSenha from './pages/EsqueciSenha.jsx'
import Login from './pages/Login.jsx'
import Painel from './pages/Painel.jsx'
import RedefinirSenha from './pages/RedefinirSenha.jsx'

export default function App() {
  const { usuario, carregando, falhaDeConexao, conferirSessao } = useAuth()

  if (carregando) {
    return <p className="carregando-tela">Carregando…</p>
  }

  if (falhaDeConexao) {
    return (
      <div className="carregando-tela">
        <Aviso>{falhaDeConexao}</Aviso>
        <button type="button" className="botao" onClick={conferirSessao}>Tentar de novo</button>
      </div>
    )
  }

  if (!usuario) {
    return (
      <Routes>
        <Route path="/login" element={<Login />} />
        <Route path="/cadastro" element={<Cadastro />} />
        <Route path="/esqueci-senha" element={<EsqueciSenha />} />
        <Route path="/redefinir-senha" element={<RedefinirSenha />} />
        <Route path="*" element={<Navigate to="/login" replace />} />
      </Routes>
    )
  }

  return (
    <Routes>
      <Route element={<Layout />}>
        <Route path="/" element={<Painel />} />
        <Route path="/contas" element={<Contas />} />
        <Route path="/categorias" element={<Categorias />} />
      </Route>
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  )
}
