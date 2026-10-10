import simbolo from '../assets/kamel-simbolo.png'

/** Logo do sistema: o panda e o nome. `grande` é a versão das telas de entrada. */
export default function Marca({ grande = false }) {
  return (
    <p className={grande ? 'marca grande' : 'marca'}>
      <img className="marca-simbolo" src={simbolo} alt="" />
      <span>
        <span className="marca-nome">Kamel</span>
        <span className="marca-sub">Controle financeiro</span>
      </span>
    </p>
  )
}
