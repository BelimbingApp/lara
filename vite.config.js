import {
    defineConfig
} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";
import { readFileSync } from 'fs';
import { resolve } from 'path';

// Read APP_ENV from environment, .env file, or default to 'local'
let appEnv = process.env.APP_ENV || 'local';
if (!process.env.APP_ENV) {
    try {
        const envFile = readFileSync(resolve(__dirname, '.env'), 'utf8');
        const match = envFile.match(/^APP_ENV=(.+)$/m);
        if (match) {
            appEnv = match[1].trim();
        }
    } catch (e) {
        // .env file not found or unreadable, use default
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
            clientPort: 443,
        },
        cors: true,
    },
});