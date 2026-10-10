const BASE = import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api'

export class ErroApi extends Error {
  constructor(status, mensagem, erros = {}) {
    super(mensagem)
    this.status = status
    this.erros = erros
  }
}

// Chamado quando a API recusa o token salvo (expirado ou apagado).
let aoPerderSessao = null

export function definirAoPerderSessao(funcao) {
  aoPerderSessao = funcao
}

export function lerToken() {
  return localStorage.getItem('token')
}

export function salvarToken(token) {
  if (token) {
    localStorage.setItem('token', token)
  } else {
    localStorage.removeItem('token')
  }
}

/**
 * Faz uma chamada à API. Envia o token quando existe, devolve o JSON da
 * resposta e transforma qualquer falha em ErroApi, com a mensagem pronta
 * para mostrar na tela.
 */
export async function api(caminho, { metodo = 'GET', corpo, query = {} } = {}) {
  const url = new URL(BASE + caminho)

  for (const [chave, valor] of Object.entries(query)) {
    if (valor !== '' && valor !== null && valor !== undefined) {
      url.searchParams.set(chave, valor)
    }
  }

  const token = lerToken()
  const headers = { Accept: 'application/json' }

  if (corpo) headers['Content-Type'] = 'application/json'
  if (token) headers.Authorization = `Bearer ${token}`

  let resposta

  try {
    resposta = await fetch(url, {
      method: metodo,
      headers,
      body: corpo ? JSON.stringify(corpo) : undefined,
    })
  } catch {
    throw new ErroApi(0, 'Não foi possível conectar à API. Confira se ela está no ar.')
  }

  if (resposta.status === 204) return null

  const dados = await resposta.json().catch(() => null)

  if (!resposta.ok) {
    if (resposta.status === 401 && token) aoPerderSessao?.()

    if (resposta.status === 429) {
      throw new ErroApi(429, 'Muitas tentativas em pouco tempo. Aguarde um minuto e tente de novo.')
    }

    throw new ErroApi(
      resposta.status,
      dados?.message ?? 'Não foi possível concluir a operação.',
      dados?.errors ?? {},
    )
  }

  return dados
}
