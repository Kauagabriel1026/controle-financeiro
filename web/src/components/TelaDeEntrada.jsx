import Marca from './Marca.jsx'

/** Moldura das telas de login e de cadastro: apresentação à esquerda, formulário à direita. */
export default function TelaDeEntrada({ children }) {
  return (
    <div className="entrada">
      <div className="entrada-marca">
        <Marca grande />
        <h2>Saiba hoje como fecha o seu mês.</h2>
        <ul>
          <li>Contas a pagar e a receber em um só lugar</li>
          <li>Baixa de pagamento com um clique</li>
          <li>Fluxo de caixa dos próximos seis meses</li>
        </ul>
      </div>

      <div className="entrada-lado">
        {children}
        <p className="entrada-projeto">
          Projeto de portfólio de Kauã Gabriel, para estudo e demonstração. Não é um banco nem um serviço financeiro.{' '}
          <a href="https://github.com/Kauagabriel1026/kamel-controle-financeiro" target="_blank" rel="noreferrer">Ver o código no GitHub</a>
        </p>
      </div>
    </div>
  )
}
