/* oxlint-disable react-hooks/exhaustive-deps --
   `buscar` é recriada a cada render; quem decide quando buscar de novo é a `chave`. */
import { useCallback, useEffect, useState } from 'react'

/**
 * Busca dados na API quando a tela abre e sempre que `chave` muda.
 * Devolve os dados, o erro, se está carregando e uma função para buscar de novo.
 */
export function useCarregar(buscar, chave = '') {
  const [estado, setEstado] = useState({ dados: null, erro: '', carregando: true })
  const [versao, setVersao] = useState(0)

  useEffect(() => {
    // Se a tela mudar antes de a resposta chegar, a resposta antiga é ignorada.
    let ativo = true

    setEstado((atual) => ({ ...atual, erro: '', carregando: true }))

    buscar()
      .then((dados) => ativo && setEstado({ dados, erro: '', carregando: false }))
      .catch((falha) => ativo && setEstado({ dados: null, erro: falha.message, carregando: false }))

    return () => {
      ativo = false
    }
  }, [chave, versao])

  const recarregar = useCallback(() => setVersao((atual) => atual + 1), [])

  return { ...estado, recarregar }
}
