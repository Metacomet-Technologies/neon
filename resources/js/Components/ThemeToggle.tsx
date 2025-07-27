import { NavbarItem } from '@/Components/catalyst/navbar';
import { useTheme } from '@/Contexts/ThemeContext';
import { ComputerDesktopIcon, MoonIcon, SunIcon } from '@heroicons/react/16/solid';
import React from 'react';

const ThemeToggle: React.FC = () => {
    const { theme, setTheme } = useTheme();

    const getNextTheme = (): 'light' | 'dark' | 'system' => {
        switch (theme) {
            case 'light':
                return 'dark';
            case 'dark':
                return 'system';
            case 'system':
            default:
                return 'light';
        }
    };

    const getThemeIcon = () => {
        switch (theme) {
            case 'light':
                return <SunIcon data-slot="icon" />;
            case 'dark':
                return <MoonIcon data-slot="icon" />;
            case 'system':
            default:
                return <ComputerDesktopIcon data-slot="icon" />;
        }
    };

    const getThemeTooltip = () => {
        const nextTheme = getNextTheme();
        return `Current: ${theme}. Click to switch to ${nextTheme}`;
    };

    const handleThemeChange = () => {
        setTheme(getNextTheme());
    };

    return (
        <NavbarItem
            onClick={handleThemeChange}
            title={getThemeTooltip()}
            aria-label={`Theme: ${theme}`}
        >
            {getThemeIcon()}
        </NavbarItem>
    );
};

export default ThemeToggle;
