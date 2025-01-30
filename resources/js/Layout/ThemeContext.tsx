import React, { ReactNode, createContext, useContext, useEffect, useState } from 'react';

type ThemeContextType = {
    theme: 'light' | 'dark';
    toggleTheme: () => void;
};

const ThemeContext = createContext<ThemeContextType | undefined>(undefined);

type ThemeProviderProps = {
    initialTheme?: 'light' | 'dark';
    children: ReactNode;
};

export const ThemeProvider: React.FC<ThemeProviderProps> = ({ initialTheme, children }) => {
    const [theme, setTheme] = useState<'light' | 'dark'>(() => {
        if (typeof window === 'undefined') {
            return initialTheme || 'dark'; // SSR-safe default
        }

        const storedTheme = document.cookie
            .split('; ')
            .find((row) => row.startsWith('theme='))
            ?.split('=')[1] as 'light' | 'dark' | undefined;

        if (storedTheme) return storedTheme;

        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    });

    useEffect(() => {
        document.documentElement.classList.toggle('dark', theme === 'dark');
        document.cookie = `theme=${theme}; path=/; max-age=31536000`;
    }, [theme]);

    const toggleTheme = () => {
        setTheme((prevTheme) => (prevTheme === 'light' ? 'dark' : 'light'));
    };

    return <ThemeContext.Provider value={{ theme, toggleTheme }}>{children}</ThemeContext.Provider>;
};

export const useTheme = (): ThemeContextType => {
    const context = useContext(ThemeContext);
    if (!context) {
        throw new Error('useTheme must be used within a ThemeProvider');
    }
    return context;
};
