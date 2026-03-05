import {
    defineConfig
} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";
import { readFileSync } from 'fs';
import { resolve } from 'path';

// Read FRONTEND_DOMAIN from env or .env file.
// Each instance (main, worktree) has its own domain; don't derive from APP_ENV.
let frontendDomain = process.env.FRONTEND_DOMAIN || '';
if (!frontendDomain) {
    try {
        const envFile = readFileSync(resolve(__dirname, '.env'), 'utf8');
        const match = envFile.match(/^FRONTEND_DOMAIN=(.+)$/m);
        if (match) {
            frontendDomain = match[1].trim().replace(/^["']|["']$/g, '');
        }
    } catch (e) {
        // .env file not found or unreadable
    }
}
frontendDomain = frontendDomain || 'local.blb.lara';

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
        origin: `https://${frontendDomain}`,
        hmr: {
            host: frontendDomain,
            protocol: 'wss',
            clientPort: 443,
        },
        cors: true,
    },
});