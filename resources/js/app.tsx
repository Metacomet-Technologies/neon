import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import * as Sentry from '@sentry/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';

import { ThemeProvider } from './Layout/ThemeContext';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

Sentry.init({
    dsn: import.meta.env.VITE_SENTRY_LARAVEL_DSN,
    integrations: [
        Sentry.replayIntegration({
            maskAllText: false,
            blockAllMedia: false,
        }),
    ],
    // Session Replay
    replaysSessionSampleRate: 0.1, // This sets the sample rate at 10%. You may want to change it to 100% while in development and then sample at a lower rate in production.
    replaysOnErrorSampleRate: 1.0, // If you're not already sampling the entire session, change the sample rate to 100% when sampling sessions where errors occur.
});

createInertiaApp({
    title: (title) => (title ? `${title} &middot; ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./Pages/${name}.tsx`, import.meta.glob('./Pages/**/*.tsx')),
    setup({ el, App, props }) {
        el.style.visibility = 'hidden';
        createRoot(el).render(
            <StrictMode>
                <ThemeProvider initialTheme={props.initialPage.props.theme}>
                    <App {...props} />
                </ThemeProvider>
            </StrictMode>
        );
        el.style.visibility = 'visible';
    },
    progress: {
        color: '#4B5563',
    },
});
