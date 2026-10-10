import { defineConfig } from '@playwright/test'

// Testes de ponta a ponta: um navegador de verdade usando o front e a API reais.
export default defineConfig({
  testDir: './e2e',
  timeout: 60_000,
  retries: 0,
  reporter: process.env.CI ? [['github'], ['list']] : 'list',
  use: {
    baseURL: 'http://localhost:5173',
    locale: 'pt-BR',
    // O mesmo fuso da API, para "hoje" ser o mesmo dia nos dois lados.
    timezoneId: 'America/Sao_Paulo',
    screenshot: 'only-on-failure',
  },
  webServer: {
    command: 'npm run preview -- --port 5173 --strictPort',
    url: 'http://localhost:5173',
    reuseExistingServer: true,
  },
})
