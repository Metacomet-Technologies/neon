import { Guild } from '@/types';
import axios from 'axios';
import React, { useEffect, useState } from 'react';
import { Select } from './catalyst/select';
import { Text } from './catalyst/text';

interface GuildSelectorProps {
    value: string;
    onChange: (guildId: string) => void;
    placeholder?: string;
    disabled?: boolean;
}

const GuildSelector: React.FC<GuildSelectorProps> = ({
    value,
    onChange,
    placeholder = 'Select a Discord server...',
    disabled = false,
}) => {
    const [guilds, setGuilds] = useState<Guild[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        fetchGuilds();
    }, []);

    const fetchGuilds = async () => {
        try {
            setLoading(true);
            const response = await axios.get('/api/guilds');
            setGuilds(response.data.guilds);
        } catch (err) {
            setError('Failed to load Discord servers');
            console.error('Error fetching guilds:', err);
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return (
            <Select disabled>
                <option>Loading servers...</option>
            </Select>
        );
    }

    if (error) {
        return (
            <div className="flex flex-col">
                <Select disabled data-invalid>
                    <option>Error loading servers</option>
                </Select>
                <Text className="text-xs text-red-600 mt-1">{error}</Text>
            </div>
        );
    }

    return (
        <Select value={value} onChange={(e) => onChange(e.target.value)} disabled={disabled} required>
            <option value="">{placeholder}</option>
            {guilds.map((guild) => (
                <option key={guild.id} value={guild.id}>
                    {guild.name}
                </option>
            ))}
        </Select>
    );
};

export default GuildSelector;
