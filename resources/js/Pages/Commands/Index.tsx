import { Button } from '@/Components/button';
import { Heading } from '@/Components/heading';
import { Listbox, ListboxOption } from '@/Components/listbox';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/table';
import { Layout } from '@/Layout/Layout';
import { Pagination } from '@/types';
import { Deferred, router } from '@inertiajs/react';
import { useCallback } from 'react';
import { Command, Guild } from './types';

export default function Index({
    commands,
    guilds,
    currentGuildId,
}: {
    commands: Pagination<Command>;
    guilds: Guild[];
    currentGuildId: string;
}) {
    const currentGuild = guilds.find((guild) => guild.id === currentGuildId);

    const handleReload = useCallback((guild: Guild) => {
        router.get(
            route('commands.index'),
            { guild_id: guild.id },
            { only: ['commands', 'currentGuildId'], preserveState: true }
        );
    }, []);

    return (
        <>
            <div className="mb-4 flex justify-between items-center gap-4">
                <Heading>Commands</Heading>

                <div className="w-md">
                    <Listbox name="guild_id" defaultValue={currentGuild} onChange={handleReload}>
                        {guilds.map((guild) => (
                            <ListboxOption value={guild} key={guild.id}>
                                {guild.name}
                            </ListboxOption>
                        ))}
                    </Listbox>
                </div>

                <Button href={route('commands.create')} color="green">
                    New Command
                </Button>
            </div>
            <Table>
                <TableHead>
                    <TableRow>
                        <TableHeader>Name</TableHeader>
                        <TableHeader>Description</TableHeader>
                        <TableHeader>Command</TableHeader>
                        <TableHeader>Enabled</TableHeader>
                        <TableHeader>Public</TableHeader>
                        <TableHeader>Created By</TableHeader>
                        <TableHeader>Updated By</TableHeader>
                        <TableHeader>Created At</TableHeader>
                        <TableHeader>Updated At</TableHeader>
                    </TableRow>
                </TableHead>

                <TableBody>
                    <Deferred data="commands" fallback={<LoadingState />}>
                        <>
                            {commands?.data.map((command: Command) => (
                                <TableRow
                                    href={route('commands.edit', { command: command.id, guild_id: 'currentGuildId' })}
                                    key={command.id}
                                >
                                    <TableCell>{command.name}</TableCell>
                                    <TableCell>{command.description}</TableCell>
                                    <TableCell>{command.command}</TableCell>
                                    <TableCell>{command.is_enabled}</TableCell>
                                    <TableCell>{command.is_public}</TableCell>
                                    <TableCell>{command.created_by_user?.name}</TableCell>
                                    <TableCell>{command.updated_by_user?.name}</TableCell>
                                    <TableCell>{command.created_at}</TableCell>
                                    <TableCell>{command.updated_at}</TableCell>
                                </TableRow>
                            ))}
                        </>
                    </Deferred>
                </TableBody>
            </Table>
        </>
    );
}

function LoadingState() {
    return (
        <TableRow>
            <TableCell colSpan={9}>Loading...</TableCell>
        </TableRow>
    );
}

Index.layout = (page: React.ReactNode) => <Layout>{page}</Layout>;
