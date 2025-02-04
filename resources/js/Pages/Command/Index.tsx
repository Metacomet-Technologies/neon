import { Button } from '@/Components/button';
import { Heading } from '@/Components/heading';
import { Link } from '@/Components/link';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/table';
import { Layout } from '@/Layout/Layout';
import PaginationRow from '@/Layout/PaginationRow';
import { Pagination } from '@/types';
import { booleanToIconForTables, formatDateTime } from '@/utils';
import { TrashIcon } from '@heroicons/react/16/solid';
import { Head } from '@inertiajs/react';
import React from 'react';
import { Command } from './types';

/**
 * Index component for displaying a list of commands.
 *
 * @param {Object} props - The component props.
 * @param {Pagination<Command>} props.commands - The paginated list of commands.
 * @param {string} props.serverId - The ID of the server.
 * @returns {JSX.Element} The rendered component.
 */
export default function Index({
    commands,
    serverId,
}: {
    commands: Pagination<Command>;
    serverId: string;
}): React.JSX.Element {
    return (
        <>
            <Head title="Commands" />
            <div className="mb-4 flex justify-between items-center gap-4">
                <Heading>Commands</Heading>

                <Button href={route('server.command.create', { serverId })} color="green">
                    New Command
                </Button>
            </div>
            <CommandTable commands={commands} serverId={serverId} />
            {commands?.links.length > 3 && <PaginationRow links={commands.links} className="mt-4" />}
        </>
    );
}

/**
 * EmptyState component for displaying a message when there are no commands.
 *
 * @param {Object} props - The component props.
 * @param {string} props.serverId - The ID of the server.
 * @returns {JSX.Element} The rendered component.
 */
function EmptyState({ serverId }: { serverId: string }): React.JSX.Element {
    return (
        <TableRow>
            <TableCell colSpan={9}>
                No commands yet... <Link href={route('server.command.create', { serverId })}>Add one now!</Link>
            </TableCell>
        </TableRow>
    );
}

Index.layout = (page: React.ReactNode) => <Layout>{page}</Layout>;

/**
 * CommandTable component for displaying a table of commands.
 *
 * @param {Object} props - The component props.
 * @param {Pagination<Command>} props.commands - The paginated list of commands.
 * @param {string} props.serverId - The ID of the server.
 * @returns {JSX.Element} The rendered component.
 */
function CommandTable({ commands, serverId }: { commands: Pagination<Command>; serverId: string }): React.JSX.Element {
    return (
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
                {commands?.data.length === 0 ? (
                    <EmptyState serverId={serverId} />
                ) : (
                    commands?.data.map((command: Command) => (
                        <TableRow
                            href={route('server.command.edit', {
                                command: command.id,
                                serverId,
                            })}
                            key={command.id}
                        >
                            <TableCell>!{command.command}</TableCell>
                            <TableCell>{command.response}</TableCell>
                            <TableCell>{booleanToIconForTables(command.is_enabled)}</TableCell>
                            <TableCell>{booleanToIconForTables(command.is_public)}</TableCell>
                            <TableCell>{formatDateTime(command.updated_at)}</TableCell>
                            <TableCell>
                                <Button
                                    href={route('server.command.destroy', {
                                        command: command.id,
                                        serverId,
                                    })}
                                    method="delete"
                                    plain
                                >
                                    <TrashIcon />
                                    Delete
                                </Button>
                            </TableCell>
                        </TableRow>
                    ))
                )}
            </TableBody>
        </Table>
    );
}
