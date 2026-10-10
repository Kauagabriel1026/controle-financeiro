/** Mensagem de erro no topo de uma tela ou formulário. */
export default function Aviso({ children }) {
  if (!children) return null

  return <p className="aviso" role="alert">{children}</p>
}
