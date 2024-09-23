import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
// import viteBasicSslPlugin from '@vitejs/plugin-basic-ssl';
// import basicSsl from '@vitejs/plugin-basic-ssl';

export default defineConfig({
    server: {
        hmr: {
            host: '192.168.6.221',
        },
    },
    esbuild: {
        supported: {
            'top-level-await': true //browsers can handle top-level-await features
        },
    },
    plugins: [
        // basicSsl(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                }
            }
        })
    ],
});
