import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import { resolve } from 'path';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/widget-entry.jsx'],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    build: {
        outDir: 'public/build',
        emptyOutDir: false,
        cssCodeSplit: false,
        
        lib: {
            entry: resolve(__dirname, 'resources/js/widget-entry.jsx'),
            name: 'ChatWidget',
            formats: ['iife'],
            fileName: () => 'widget.js',
        },
        rollupOptions: {
            external: [],
            output: {
                globals: {},
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name && (assetInfo.name.endsWith('.css') || assetInfo.name === 'style.css')) {
                        return 'widget.css';
                    }
                    return assetInfo.name;
                },
            },
        },
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        origin: 'http://localhost:5173',
        hmr: {
            host: 'localhost',
        },
        cors: {
            origin: 'http://localhost:8000', 
            credentials: true,
        },
    },
});