import { Button } from '@/Components/button';
import { Heading } from '@/Components/heading';
import { Link } from '@/Components/link';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/table';
import AdminLayout from '@/Layout/AdminLayout';
import PaginationRow from '@/Layout/PaginationRow';
import { Pagination } from '@/types';
import { booleanToIconForTables, formatDateTime } from '@/utils';
import { TrashIcon } from '@heroicons/react/16/solid';
import { Head } from '@inertiajs/react';
import React from 'react';
import { NativeCommand } from './types';

/**
 * Index component for displaying a list of nativeCommands.
 *
 * @param {Object} props - The component props.
 * @param {Pagination<NativeCommand>} props.nativeCommands - The paginated list of nativeCommands.
 * @param {string} props.serverId - The ID of the server.
 * @returns {JSX.Element} The rendered component.
 */
export default function Index({
    nativeCommands,
    serverId,
}: {
    nativeCommands: Pagination<NativeCommand>;
    serverId: string;
}): React.JSX.Element {
    const removePathFromClass = (path: string): string => {
        return path.replace('App\\Jobs\\', '');
    };

    return (
        <>
            <Head title="Native Commands" />
            <div className="mb-4 flex justify-between items-center gap-4">
                <Heading>Native Commands</Heading>

                <Button href="#" color="green">
                    New Command
                </Button>
            </div>
            <Table striped dense className="[--gutter:--spacing(6)] sm:[--gutter:--spacing(8)]">
                <TableHead>
                    <TableRow>
                        <TableHeader>Command</TableHeader>
                        <TableHeader>Description</TableHeader>
                        <TableHeader>Class</TableHeader>
                        <TableHeader>Active</TableHeader>
                        <TableHeader>Last Updated</TableHeader>
                        <TableHeader />
                    </TableRow>
                </TableHead>

                <TableBody>
                    {nativeCommands?.data.length === 0 ? (
                        <EmptyState serverId={serverId} />
                    ) : (
                        nativeCommands?.data.map((command: NativeCommand) => (
                            <TableRow href="#" key={command.id}>
                                <TableCell>!{command.slug}</TableCell>
                                <TableCell>{command.description}</TableCell>
                                <TableCell>{removePathFromClass(command.class)}</TableCell>
                                <TableCell>{booleanToIconForTables(command.is_active)}</TableCell>
                                <TableCell>{formatDateTime(command.updated_at)}</TableCell>
                                <TableCell>
                                    <Button href="#" method="delete" plain>
                                        <TrashIcon />
                                        Delete
                                    </Button>
                                </TableCell>
                            </TableRow>
                        ))
                    )}
                </TableBody>
            </Table>
            {nativeCommands?.links.length > 3 && <PaginationRow links={nativeCommands.links} className="mt-4" />}
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
                No commands yet... <Link href="#">Add one now!</Link>
            </TableCell>
        </TableRow>
    );
}

Index.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
