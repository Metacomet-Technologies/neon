import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';

import { ThemeProvider } from './Contexts/ThemeContext';
import { ToastProvider } from './Contexts/ToastContext';

const appName = import.meta.env.VITE_APP_NAME || 'Neon';

createInertiaApp({
    title: (title) => (title ? `${title} &middot; ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./Pages/${name}.tsx`, import.meta.glob('./Pages/**/*.tsx')),
    setup({ el, App, props }) {
        createRoot(el).render(
            <StrictMode>
                <ThemeProvider initialTheme={props.initialPage.props.theme}>
                    <ToastProvider>
                        <App {...props} />
                    </ToastProvider>
                </ThemeProvider>
            </StrictMode>
        );
    },
    progress: {
        color: '#4B5563',
    },
});
