import { Guild } from '@/types';
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function getGuildIcon(guild: Guild | null) {
    if (guild?.icon) {
        return `https://cdn.discordapp.com/icons/${guild.id}/${guild.icon}.png`;
    }

    return `https://ui-avatars.com/api/?name=${encodeURIComponent(guild?.name || 'Unknown')}&background=random`;
}
