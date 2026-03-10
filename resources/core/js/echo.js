const driver = window.__BLB_BROADCAST_DRIVER__
const useReverb = driver === 'reverb' && import.meta.env.VITE_REVERB_APP_KEY
const usePusher = driver === 'pusher' && import.meta.env.VITE_PUSHER_APP_KEY

if (useReverb) {
    const Echo = (await import('laravel-echo')).default
    const Pusher = (await import('pusher-js')).default
    // Use app host (Caddy) when page is HTTPS so wss works; Caddy proxies to Reverb
    const isSecure = typeof location !== 'undefined' && location.protocol === 'https:'
    const wsHost = isSecure ? location.hostname : (import.meta.env.VITE_REVERB_HOST || 'localhost')
    const wssPort = isSecure ? (parseInt(location.port, 10) || 443) : (import.meta.env.VITE_REVERB_PORT ?? 443)
    const useTLS = isSecure
    window.Pusher = Pusher
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort,
        forceTLS: useTLS,
        enabledTransports: useTLS ? ['wss'] : ['ws', 'wss'],
    })
} else if (usePusher) {
    const Echo = (await import('laravel-echo')).default
    const Pusher = (await import('pusher-js')).default
    window.Pusher = Pusher
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: import.meta.env.VITE_PUSHER_APP_KEY,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
    })
}
