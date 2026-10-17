import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
  plugins: [vue()],
  resolve: { alias: { '@': fileURLToPath(new URL('./src', import.meta.url)) } },
  // server: { port: 5173, strictPort: true },
    server: { allowedHosts: ['saurabh.tail3df8f7.ts.net'] }
})
