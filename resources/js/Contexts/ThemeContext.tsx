import React, { createContext, useContext, useEffect, useState } from 'react';
import { router } from '@inertiajs/react';

type Theme = 'light' | 'dark' | 'system';

interface ThemeContextType {
    theme: Theme;
    setTheme: (theme: Theme) => void;
    resolvedTheme: 'light' | 'dark';
}

const ThemeContext = createContext<ThemeContextType | undefined>(undefined);

interface ThemeProviderProps {
    children: React.ReactNode;
    initialTheme?: Theme;
}

export function ThemeProvider({ children, initialTheme = 'system' }: ThemeProviderProps) {
    const [theme, setThemeState] = useState<Theme>(initialTheme);
    const [resolvedTheme, setResolvedTheme] = useState<'light' | 'dark'>('dark');

    // Function to get system preference
    const getSystemTheme = (): 'light' | 'dark' => {
        if (typeof window === 'undefined') return 'dark';
        
        try {
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        } catch {
            return 'dark';
        }
    };

    // Function to resolve the actual theme to apply
    const resolveTheme = (currentTheme: Theme): 'light' | 'dark' => {
        if (currentTheme === 'system') {
            return getSystemTheme();
        }
        return currentTheme;
    };

    // Function to apply theme to DOM
    const applyTheme = (themeToApply: 'light' | 'dark') => {
        if (typeof window === 'undefined') return;
        
        const root = window.document.documentElement;
        
        if (themeToApply === 'dark') {
            root.classList.add('dark');
        } else {
            root.classList.remove('dark');
        }
    };

    // Set theme and persist to backend
    const setTheme = (newTheme: Theme) => {
        setThemeState(newTheme);
        
        // Persist to backend
        router.post('/api/user/settings', {
            theme: newTheme,
        }, {
            preserveState: true,
            preserveScroll: true,
            only: [], // Don't reload any props
        });
    };

    // Update resolved theme when theme changes
    useEffect(() => {
        const resolved = resolveTheme(theme);
        setResolvedTheme(resolved);
        applyTheme(resolved);
    }, [theme]);

    // Listen for system theme changes
    useEffect(() => {
        if (typeof window === 'undefined') return;

        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        
        const handleChange = () => {
            if (theme === 'system') {
                const resolved = resolveTheme('system');
                setResolvedTheme(resolved);
                applyTheme(resolved);
            }
        };

        mediaQuery.addEventListener('change', handleChange);
        
        return () => mediaQuery.removeEventListener('change', handleChange);
    }, [theme]);

    // Apply theme on mount
    useEffect(() => {
        const resolved = resolveTheme(theme);
        setResolvedTheme(resolved);
        applyTheme(resolved);
    }, []);

    return (
        <ThemeContext.Provider value={{ theme, setTheme, resolvedTheme }}>
            {children}
        </ThemeContext.Provider>
    );
}

export function useTheme(): ThemeContextType {
    const context = useContext(ThemeContext);
    if (!context) {
        throw new Error('useTheme must be used within a ThemeProvider');
    }
    return context;
}