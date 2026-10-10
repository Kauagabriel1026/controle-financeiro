import { centavosParaTexto } from '../formato.js'

/**
 * Campo de valor em reais com máscara: a pessoa digita só os números e os
 * centavos entram pela direita, como em aplicativo de banco.
 * Digitar 1, 6, 0, 0, 0, 0 mostra 0,01 → 0,16 → 1,60 → 16,00 → 160,00 → 1.600,00.
 * O valor é guardado em centavos (número inteiro) para não haver erro de arredondamento.
 */
export default function CampoValor({ centavos, aoMudar, ...resto }) {
  function mudar(evento) {
    const digitos = evento.target.value.replace(/\D/g, '').slice(0, 12)

    aoMudar(Number(digitos))
  }

  return (
    <input
      type="text"
      inputMode="numeric"
      autoComplete="off"
      placeholder="0,00"
      value={centavos > 0 ? centavosParaTexto(centavos) : ''}
      onChange={mudar}
      {...resto}
    />
  )
}
