import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react'

import { api, definirAoPerderSessao, lerToken, salvarToken } from './api.js'

const AuthContext = createContext(null)

export function AuthProvider({ children }) {
  const [usuario, setUsuario] = useState(null)
  // Enquanto o token salvo não é conferido, não dá para saber se há sessão.
  const [carregando, setCarregando] = useState(() => lerToken() !== null)
  const [falhaDeConexao, setFalhaDeConexao] = useState('')

  const encerrar = useCallback(() => {
    salvarToken(null)
    setUsuario(null)
  }, [])

  // Confere o token salvo. Só um 401 encerra a sessão (isso já acontece dentro
  // de `api`); se a API estiver fora do ar, o token é mantido para tentar de novo.
  const buscarUsuario = useCallback(() => {
    api('/eu')
      .then(setUsuario)
      .catch((falha) => {
        if (falha.status !== 401) setFalhaDeConexao(falha.message)
      })
      .finally(() => setCarregando(false))
  }, [])

  // Usada pelo botão "Tentar de novo" da tela de falha de conexão.
  const conferirSessao = useCallback(() => {
    setFalhaDeConexao('')
    setCarregando(true)
    buscarUsuario()
  }, [buscarUsuario])

  useEffect(() => {
    definirAoPerderSessao(encerrar)

    if (lerToken()) buscarUsuario()
  }, [encerrar, buscarUsuario])

  const valor = useMemo(() => {
    const iniciar = ({ usuario: novoUsuario, token }) => {
      salvarToken(token)
      setUsuario(novoUsuario)
    }

    return {
      usuario,
      carregando,
      falhaDeConexao,
      conferirSessao,
      entrar: async (dados) => iniciar(await api('/login', { metodo: 'POST', corpo: dados })),
      cadastrar: async (dados) => iniciar(await api('/registrar', { metodo: 'POST', corpo: dados })),
      // Sai na hora neste navegador e avisa a API em seguida. O pedido sai antes
      // de o token ser apagado e continua mesmo se a página for fechada.
      sair: async () => {
        const pedido = api('/logout', { metodo: 'POST', manterVivo: true }).catch(() => {})
        encerrar()
        await pedido
      },
    }
  }, [usuario, carregando, falhaDeConexao, conferirSessao, encerrar])

  return <AuthContext.Provider value={valor}>{children}</AuthContext.Provider>
}

// oxlint-disable-next-line react/only-export-components
export function useAuth() {
  return useContext(AuthContext)
}
