import { NavbarItem } from '@/Components/navbar';
import { useTheme } from '@/Layout/ThemeContext';
import { MoonIcon, SunIcon } from '@heroicons/react/20/solid';

export default function ThemeToggleButton({ ...props }) {
    const { theme, toggleTheme } = useTheme();

    return (
        <NavbarItem {...props} onClick={toggleTheme}>
            {theme === 'light' ? <SunIcon /> : <MoonIcon />}
        </NavbarItem>
    );
}
