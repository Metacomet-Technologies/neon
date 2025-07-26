import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';

import { ThemeProvider } from './Contexts/ThemeContext';

const appName = import.meta.env.VITE_APP_NAME || 'Neon';

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
