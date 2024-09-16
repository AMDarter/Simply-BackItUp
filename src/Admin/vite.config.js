import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [react()],
    build: {
        outDir: './dist',
        emptyOutDir: true,
        rollupOptions: {
            input: './src/main.jsx',
            output: {
                // Keep all assets in the root directory of 'dist'
                entryFileNames: '[name]-[hash].js',
                chunkFileNames: '[name]-[hash].js',
                assetFileNames: '[name]-[hash][extname]',
            },
        },
        assetsInlineLimit: 0,
    },
});
