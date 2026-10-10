import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react'

import { api, definirAoPerderSessao, lerToken, salvarToken } from './api.js'

const AuthContext = createContext(null)

export function AuthProvider({ children }) {
  const [usuario, setUsuario] = useState(null)
  // Enquanto o token salvo não é conferido, não dá para saber se há sessão.
  const [carregando, setCarregando] = useState(() => lerToken() !== null)

  const encerrar = useCallback(() => {
    salvarToken(null)
    setUsuario(null)
  }, [])

  useEffect(() => {
    definirAoPerderSessao(encerrar)

    if (!lerToken()) return

    api('/eu')
      .then(setUsuario)
      .catch(() => encerrar())
      .finally(() => setCarregando(false))
  }, [encerrar])

  const valor = useMemo(() => {
    const iniciar = ({ usuario: novoUsuario, token }) => {
      salvarToken(token)
      setUsuario(novoUsuario)
    }

    return {
      usuario,
      carregando,
      entrar: async (dados) => iniciar(await api('/login', { metodo: 'POST', corpo: dados })),
      cadastrar: async (dados) => iniciar(await api('/registrar', { metodo: 'POST', corpo: dados })),
      sair: async () => {
        await api('/logout', { metodo: 'POST' }).catch(() => {})
        encerrar()
      },
    }
  }, [usuario, carregando, encerrar])

  return <AuthContext.Provider value={valor}>{children}</AuthContext.Provider>
}

// oxlint-disable-next-line react/only-export-components
export function useAuth() {
  return useContext(AuthContext)
}
