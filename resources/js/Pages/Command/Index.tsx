import { Button } from '@/Components/button';
import { Heading } from '@/Components/heading';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/table';
import { Layout } from '@/Layout/Layout';
import { Pagination } from '@/types';
import { booleanToIconForTables, formatDateTime } from '@/utils';
import { TrashIcon } from '@heroicons/react/16/solid';
import { Deferred } from '@inertiajs/react';
import { Command } from './types';

export default function Index({ commands, serverId }: { commands: Pagination<Command>; serverId: string }) {
    return (
        <>
            <div className="mb-4 flex justify-between items-center gap-4">
                <Heading>Commands</Heading>

                <Button href={route('server.command.create', { serverId: serverId })} color="green">
                    New Command
                </Button>
            </div>
            <Table striped dense className="[--gutter:--spacing(6)] sm:[--gutter:--spacing(8)]">
                <TableHead>
                    <TableRow>
                        <TableHeader>Command</TableHeader>
                        <TableHeader>Response</TableHeader>
                        <TableHeader>Enabled</TableHeader>
                        <TableHeader>Public</TableHeader>
                        <TableHeader>Last Updated</TableHeader>
                        <TableHeader />
                    </TableRow>
                </TableHead>

                <TableBody>
                    <Deferred data="commands" fallback={<LoadingState />}>
                        <>
                            {commands?.data.map((command: Command) => (
                                <TableRow
                                    href={route('server.command.edit', {
                                        command: command.id,
                                        serverId: serverId,
                                    })}
                                    key={command.id}
                                >
                                    <TableCell>{command.command}</TableCell>
                                    <TableCell>{command.response}</TableCell>
                                    <TableCell>{booleanToIconForTables(command.is_enabled)}</TableCell>
                                    <TableCell>{booleanToIconForTables(command.is_public)}</TableCell>
                                    <TableCell>{formatDateTime(command.updated_at)}</TableCell>
                                    <TableCell>
                                        <Button
                                            href={route('server.command.destroy', {
                                                command: command.id,
                                                serverId: serverId,
                                            })}
                                            method="delete"
                                            plain
                                        >
                                            <TrashIcon />
                                            Delete
                                        </Button>
                                    </TableCell>
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

function EmptyState() {
    return (
        <TableRow>
            <TableCell colSpan={9}>No commands found.</TableCell>
        </TableRow>
    );
}

Index.layout = (page: React.ReactNode) => <Layout>{page}</Layout>;
