import { expect, test } from '@playwright/test'

import { dataBR, hojeISO, nomeDoMes } from '../src/formato.js'

const SENHA = 'senha-de-teste'

function diasAtras(dias) {
  const data = new Date()
  data.setDate(data.getDate() - dias)

  return hojeISO(data)
}

async function criarUsuario(page, nome) {
  const email = `${nome.toLowerCase()}-${Date.now()}@example.com`

  await page.goto('/')
  await page.getByRole('link', { name: 'Criar conta' }).click()
  await page.getByLabel('Nome').fill(nome)
  await page.getByLabel('E-mail').fill(email)
  await page.locator('input[autocomplete="new-password"]').nth(0).fill(SENHA)
  await page.locator('input[autocomplete="new-password"]').nth(1).fill(SENHA)
  await page.getByRole('button', { name: 'Criar conta' }).click()
  await expect(page.getByRole('heading', { level: 1 })).toHaveText(nomeDoMes(hojeISO().slice(0, 7)))

  return email
}

async function criarConta(page, { tipo, descricao, valor, vencimento, categoria }) {
  await page.getByRole('button', { name: 'Nova conta' }).click()

  const janela = page.getByRole('dialog')

  await janela.getByLabel(tipo).check()
  await janela.getByLabel('Descrição').fill(descricao)
  await janela.getByLabel('Valor (R$)').fill(valor)
  await janela.getByLabel('Vencimento').fill(vencimento)
  if (categoria) await janela.getByLabel('Categoria').selectOption({ label: categoria })
  await janela.getByRole('button', { name: 'Salvar conta' }).click()
  await expect(janela).toBeHidden()
}

test('fluxo completo: cadastro, categorias, contas, baixa, painel e sessão', async ({ page }) => {
  // Qualquer erro de JavaScript na página reprova o teste. Respostas 4xx
  // esperadas (validação, senha errada) aparecem no console e são ignoradas.
  const erros = []
  page.on('pageerror', (erro) => erros.push(erro.message))
  page.on('console', (mensagem) => {
    if (mensagem.type() === 'error' && !mensagem.text().includes('Failed to load resource')) {
      erros.push(mensagem.text())
    }
  })
  page.on('dialog', (dialogo) => dialogo.accept())

  const ontem = diasAtras(1)
  const hoje = hojeISO()

  // --- Cadastro: usuário novo começa com o painel vazio ---
  const email = await criarUsuario(page, 'Ana')
  await expect(page.getByText('Nenhuma conta com vencimento neste período.')).toBeVisible()

  // --- Categorias: criar, recusar nome repetido, renomear ---
  await page.getByRole('link', { name: 'Categorias' }).click()
  await page.getByLabel('Nova categoria').fill('Moradia')
  await page.getByRole('button', { name: 'Adicionar categoria' }).click()
  await expect(page.getByRole('listitem').filter({ hasText: 'Moradia' })).toBeVisible()

  await page.getByLabel('Nova categoria').fill('Moradia')
  await page.getByRole('button', { name: 'Adicionar categoria' }).click()
  await expect(page.getByRole('alert')).toHaveText('Já existe um cadastro com esse nome.')

  await page.getByLabel('Nova categoria').fill('Renda')
  await page.getByRole('button', { name: 'Adicionar categoria' }).click()
  await page.getByRole('listitem').filter({ hasText: 'Renda' }).getByRole('button', { name: 'Renomear' }).click()
  await page.getByLabel('Novo nome').fill('Salário')
  await page.getByRole('button', { name: 'Salvar' }).click()
  await expect(page.getByRole('listitem').filter({ hasText: 'Salário' })).toBeVisible()

  // --- Contas: criar uma vencida e uma pendente ---
  await page.getByRole('link', { name: 'Contas' }).click()
  await expect(page.getByText('Nenhuma conta com esses filtros.')).toBeVisible()

  await criarConta(page, { tipo: 'A pagar', descricao: 'Aluguel', valor: '1500.10', vencimento: ontem, categoria: 'Moradia' })
  await criarConta(page, { tipo: 'A receber', descricao: 'Pagamento do mês', valor: '5200', vencimento: hoje, categoria: 'Salário' })

  await page.getByRole('button', { name: 'Ver todos os meses' }).click()

  const aluguel = page.getByRole('row').filter({ hasText: 'Aluguel' })
  const pagamento = page.getByRole('row').filter({ hasText: 'Pagamento do mês' })

  await expect(aluguel).toContainText(dataBR(ontem))
  await expect(aluguel).toContainText('R$ 1.500,10')
  await expect(aluguel).toContainText('Moradia')
  await expect(aluguel).toContainText('Vencida')
  await expect(pagamento).toContainText('Pendente')

  // --- Validação: valor zero é recusado antes de sair do formulário ---
  await page.getByRole('button', { name: 'Nova conta' }).click()
  await page.getByRole('dialog').getByLabel('Descrição').fill('Conta inválida')
  await page.getByRole('dialog').getByLabel('Valor (R$)').fill('0')
  await page.getByRole('dialog').getByRole('button', { name: 'Salvar conta' }).click()
  await expect(page.getByRole('dialog')).toBeVisible()
  await page.getByRole('dialog').getByRole('button', { name: 'Cancelar' }).click()
  await expect(page.getByRole('row').filter({ hasText: 'Conta inválida' })).toHaveCount(0)

  // --- Painel: avisa da conta vencida e leva até ela ---
  await page.getByRole('link', { name: 'Painel' }).click()
  await expect(page.getByText('Há 1 conta vencida em aberto.')).toBeVisible()
  await expect(page.getByLabel('Resumo do mês')).toContainText('R$ 5.200,00')
  await page.getByRole('link', { name: 'Ver contas vencidas' }).click()
  await expect(aluguel).toBeVisible()
  await expect(pagamento).toHaveCount(0)

  // --- Baixa, estorno e baixa de novo ---
  await aluguel.getByRole('button', { name: 'Dar baixa' }).click()
  await expect(aluguel).toHaveCount(0) // saiu do filtro "vencidas"

  await page.getByLabel('Situação').selectOption('paga')
  await expect(aluguel).toContainText('Paga')
  await expect(aluguel).toContainText(`em ${dataBR(hoje)}`)

  await aluguel.getByRole('button', { name: 'Estornar' }).click()
  await expect(aluguel).toHaveCount(0)

  await page.getByLabel('Situação').selectOption('')
  await expect(aluguel).toContainText('Vencida')
  await aluguel.getByRole('button', { name: 'Dar baixa' }).click()
  await expect(aluguel).toContainText('Paga')

  // --- Edição ---
  await pagamento.getByRole('button', { name: 'Editar' }).click()
  await page.getByRole('dialog').getByLabel('Valor (R$)').fill('5300')
  await page.getByRole('dialog').getByRole('button', { name: 'Salvar conta' }).click()
  await expect(pagamento).toContainText('R$ 5.300,00')

  // --- Filtro por tipo ---
  await page.getByLabel('Tipo').selectOption('receber')
  await expect(pagamento).toBeVisible()
  await expect(aluguel).toHaveCount(0)
  await page.getByLabel('Tipo').selectOption('')

  // --- Painel reflete a edição e a baixa ---
  await page.getByRole('link', { name: 'Painel' }).click()
  await expect(page.getByLabel('Resumo do mês')).toContainText('R$ 5.300,00')
  await expect(page.getByText('conta vencida em aberto')).toHaveCount(0)

  // --- Exclusão ---
  await page.getByRole('link', { name: 'Contas' }).click()
  await page.getByRole('button', { name: 'Ver todos os meses' }).click()
  await aluguel.getByRole('button', { name: 'Excluir' }).click()
  await expect(aluguel).toHaveCount(0)
  await expect(pagamento).toBeVisible()

  // --- Sessão: continua logado ao recarregar; sair volta para o login ---
  await page.reload()
  await expect(page.getByRole('heading', { name: 'Contas' })).toBeVisible()

  await page.getByRole('button', { name: 'Sair' }).click()
  await expect(page.getByRole('heading', { name: 'Entrar' })).toBeVisible()

  // --- Login: senha errada é recusada, senha certa entra ---
  await page.getByLabel('E-mail').fill(email)
  await page.getByLabel('Senha').fill('senha-errada')
  await page.getByRole('button', { name: 'Entrar' }).click()
  await expect(page.getByRole('alert')).toHaveText('E-mail ou senha inválidos.')

  await page.getByLabel('Senha').fill(SENHA)
  await page.getByRole('button', { name: 'Entrar' }).click()
  await expect(page.getByRole('heading', { level: 1 })).toHaveText(nomeDoMes(hoje.slice(0, 7)))

  // --- Isolamento: outro usuário não vê nada da Ana ---
  await page.getByRole('button', { name: 'Sair' }).click()
  await expect(page.getByRole('heading', { name: 'Entrar' })).toBeVisible()
  await criarUsuario(page, 'Bruno')
  await page.getByRole('link', { name: 'Contas' }).click()
  await page.getByRole('button', { name: 'Ver todos os meses' }).click()
  await expect(page.getByText('Nenhuma conta com esses filtros.')).toBeVisible()
  await page.getByRole('link', { name: 'Categorias' }).click()
  await expect(page.getByText('Nenhuma categoria ainda.')).toBeVisible()

  expect(erros).toEqual([])
})
