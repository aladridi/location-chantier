import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
    plugins: [vue()],
    root: 'public',  // ✅ Ajout : Définir public comme racine
    server: {
        port: 5173,
        proxy: {
            '/api': {
                target: 'http://localhost:8000',
                changeOrigin: true
            }
        }
    },
    build: {
        outDir: '../public/dist',
        assetsDir: 'assets',
        rollupOptions: {
            input: {
                app: path.resolve(__dirname, 'public/assets/js/app.js')
            }
        }
    },
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'public/assets/js')
        }
    },
    optimizeDeps: {
        include: ['bootstrap', 'chart.js', 'vue', 'pinia', 'vue-router', 'axios']
    }
})