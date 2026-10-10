import react from '@vitejs/plugin-react'
import { defineConfig } from 'vite'

export default defineConfig({
  plugins: [react()],
  server: {
    host: true,
    port: 5173,
    // No Docker em Windows as alterações de arquivo só são percebidas por polling.
    watch: { usePolling: true },
  },
  test: {
    environment: 'node',
    // Os testes de ponta a ponta (pasta e2e) rodam com o Playwright, não com o Vitest.
    include: ['src/**/*.test.js'],
  },
})
