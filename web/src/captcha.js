// Captcha da Cloudflare (Turnstile). Sem a chave do site, como no ambiente local
// e nos testes automáticos, ele não aparece nem é exigido.
export const CHAVE_DO_SITE = import.meta.env.VITE_TURNSTILE_SITE_KEY
export const CAPTCHA_ATIVO = Boolean(CHAVE_DO_SITE)

const SCRIPT = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit'
let carregamento = null

/** Carrega o script da Cloudflare uma única vez, na primeira tela que precisa dele. */
export function carregarTurnstile() {
  carregamento ??= new Promise((resolver, rejeitar) => {
    const script = document.createElement('script')
    script.src = SCRIPT
    script.async = true
    script.onload = () => resolver(window.turnstile)
    script.onerror = () => {
      carregamento = null
      rejeitar(new Error('captcha'))
    }
    document.head.appendChild(script)
  })

  return carregamento
}
