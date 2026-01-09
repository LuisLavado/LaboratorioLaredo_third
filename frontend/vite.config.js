import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [react()],
  base: '/', // Asegurar que las rutas sean absolutas desde la raíz
  server: {
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
    },
    // Configuración para manejar rutas de SPA en desarrollo
    historyApiFallback: true,
  },
  build: {
    // Asegurar que el .htaccess se copie al build
    rollupOptions: {
      input: {
        main: './index.html',
      },
    },
  },
  // Configuración para preview (servidor de producción local)
  preview: {
    port: 4173,
    // Manejar rutas de SPA en preview
    historyApiFallback: true,
  },
})
