import { Guild } from '@/types';
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

/**
 * Combine class names and apply Tailwind CSS JIT.
 *
 * @param inputs The class names to combine.
 */
export function cn(...inputs: ClassValue[]): string {
    return twMerge(clsx(inputs));
}

/**
 * Get the guild icon URL or generate a placeholder icon.
 *
 * @param guild The guild to get the icon for.
 */
export function getGuildIcon(guild: Guild | null): string {
    if (guild?.icon) {
        return `https://cdn.discordapp.com/icons/${guild.id}/${guild.icon}.png`;
    }

    const url = new URL('https://ui-avatars.com/api/');
    const params: { [key: string]: string | number } = {
        name: guild?.name || 'Unknown',
        background: '5865F2',
        color: 'FFFFFF',
        size: 128,
    };

    Object.keys(params).forEach((key) => url.searchParams.append(key, params[key] as string));

    return url.toString();
}
