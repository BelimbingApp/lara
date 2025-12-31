import {
    defineConfig
} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";
import { readFileSync } from 'fs';
import { resolve } from 'path';

// Read APP_ENV from environment, .env file, or default to 'local'
let appEnv = process.env.APP_ENV || 'local';
let httpsPort = process.env.HTTPS_PORT || '443';
if (!process.env.APP_ENV || !process.env.HTTPS_PORT) {
    try {
        const envFile = readFileSync(resolve(__dirname, '.env'), 'utf8');
        if (!process.env.APP_ENV) {
            const envMatch = envFile.match(/^APP_ENV=(.+)$/m);
            if (envMatch) {
                appEnv = envMatch[1].trim();
            }
        }
        if (!process.env.HTTPS_PORT) {
            const portMatch = envFile.match(/^HTTPS_PORT=(.+)$/m);
            if (portMatch) {
                httpsPort = portMatch[1].trim();
            }
        }
    } catch (e) {
        // .env file not found or unreadable, use defaults
    }
}
const domain = `${appEnv}.blb.lara`;

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: [
                'resources/views/**',
                'resources/css/**',
                'resources/js/**',
            ],
        }),
        tailwindcss(),
    ],
    server: {
        host: '127.0.0.1',
        port: parseInt(process.env.VITE_PORT || '5173'),
        strictPort: true,
        hmr: {
            host: domain,
            protocol: 'wss',
            clientPort: parseInt(httpsPort),
        },
        cors: true,
    },
});