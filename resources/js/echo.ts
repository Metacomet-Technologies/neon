import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Extend the global Window interface to include the Echo instance
declare global {
    interface Window {
        Pusher: typeof Pusher;
        Echo: Echo<any>;
    }
}

window.Pusher = Pusher;

window.Echo = new Echo<any>({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY as string,
    wsHost: import.meta.env.VITE_REVERB_HOST as string,
    wsPort: (import.meta.env.VITE_REVERB_PORT as number) ?? 80,
    wssPort: (import.meta.env.VITE_REVERB_PORT as number) ?? 443,
    forceTLS: ((import.meta.env.VITE_REVERB_SCHEME as string) ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
