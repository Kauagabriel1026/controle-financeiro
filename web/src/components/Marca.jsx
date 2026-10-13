import { Link } from 'react-router-dom'

import simbolo from '../assets/kamel-simbolo.png'

/**
 * Logo do sistema: o panda e o nome. `grande` é a versão das telas de entrada.
 * Com `para`, vira um link (no menu lateral, leva ao painel).
 */
export default function Marca({ grande = false, para }) {
  const conteudo = (
    <>
      <img className="marca-simbolo" src={simbolo} alt="" />
      <span>
        <span className="marca-nome">Kamel</span>
        <span className="marca-sub">Controle financeiro</span>
      </span>
    </>
  )
  const classe = grande ? 'marca grande' : 'marca'

  return para ? <Link className={classe} to={para}>{conteudo}</Link> : <p className={classe}>{conteudo}</p>
}
