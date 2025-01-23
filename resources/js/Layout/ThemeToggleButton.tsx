import { NavbarItem } from '@/Components/navbar';
import { useTheme } from '@/Layout/ThemeContext';
import { MoonIcon, SunIcon } from '@heroicons/react/20/solid';

export default function ThemeToggleButton() {
    const { theme, toggleTheme } = useTheme();

    return <NavbarItem onClick={toggleTheme}>{theme === 'light' ? <SunIcon /> : <MoonIcon />}</NavbarItem>;
}
