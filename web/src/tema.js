// Tema da interface. A preferência (claro, escuro ou sistema) fica salva neste
// navegador para valer já ao abrir a página, antes de a API responder; a da
// conta, que vem da API, tem a palavra final.
const CHAVE = 'tema'
const escuroNoSistema = window.matchMedia('(prefers-color-scheme: dark)')

let preferencia = 'sistema'

function aplicar() {
  const efetivo = preferencia === 'sistema' ? (escuroNoSistema.matches ? 'escuro' : 'claro') : preferencia
  document.documentElement.dataset.tema = efetivo
}

// Com "sistema", acompanha a troca de tema do aparelho na hora.
escuroNoSistema.addEventListener('change', aplicar)

export function definirTema(novaPreferencia) {
  preferencia = novaPreferencia ?? 'sistema'
  aplicar()

  try {
    localStorage.setItem(CHAVE, preferencia)
  } catch {
    // Sem acesso ao armazenamento (modo privado), o tema vale só nesta visita.
  }
}

export function temaSalvo() {
  try {
    return localStorage.getItem(CHAVE) ?? 'sistema'
  } catch {
    return 'sistema'
  }
}

/** O tema que está na tela agora: "claro" ou "escuro". */
export function temaEfetivo() {
  return document.documentElement.dataset.tema
}
