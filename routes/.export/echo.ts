import Pusher from 'pusher-js';
import { configureEcho } from '@laravel/echo-react';

// Extend the global Window interface to include Pusher
declare global {
    interface Window {
        Pusher: typeof Pusher;
    }
}

window.Pusher = Pusher;

// Configure Echo for use with @laravel/echo-react
configureEcho({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY as string,
    wsHost: import.meta.env.VITE_REVERB_HOST as string,
    wsPort: (import.meta.env.VITE_REVERB_PORT as number) ?? 80,
    wssPort: (import.meta.env.VITE_REVERB_PORT as number) ?? 443,
    forceTLS: ((import.meta.env.VITE_REVERB_SCHEME as string) ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
