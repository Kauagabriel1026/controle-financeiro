import { useEffect, useRef } from 'react'

import { CAPTCHA_ATIVO, CHAVE_DO_SITE, carregarTurnstile } from '../captcha.js'
import { temaEfetivo } from '../tema.js'

/**
 * Verificação "não sou um robô". Entrega o token em `aoMudar`, e '' quando ele
 * expira. Cada token vale para um único envio: depois de usar, a tela troca a
 * `key` do componente para gerar outro. `aoMudar` e `aoFalhar` devem ser
 * funções estáveis (as de useState servem).
 */
export default function Captcha({ aoMudar, aoFalhar }) {
  const caixa = useRef(null)

  useEffect(() => {
    if (!CAPTCHA_ATIVO) return undefined

    let id = null
    let ativo = true

    carregarTurnstile()
      .then((turnstile) => {
        if (!ativo || !caixa.current) return
        id = turnstile.render(caixa.current, {
          sitekey: CHAVE_DO_SITE,
          language: 'pt-br',
          size: 'flexible',
          theme: temaEfetivo() === 'escuro' ? 'dark' : 'light',
          callback: (token) => aoMudar(token),
          'expired-callback': () => aoMudar(''),
          'error-callback': () => aoMudar(''),
        })
      })
      .catch(() => aoFalhar('Não foi possível carregar a verificação de segurança. Confira sua conexão e recarregue a página.'))

    return () => {
      ativo = false
      if (id !== null) window.turnstile?.remove(id)
    }
  }, [aoMudar, aoFalhar])

  if (!CAPTCHA_ATIVO) return null

  return <div className="captcha" ref={caixa} />
}
