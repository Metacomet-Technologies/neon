import { Button } from '@/Components/catalyst/button';
import { Dialog, DialogActions, DialogDescription, DialogTitle } from '@/Components/catalyst/dialog';
import { Strong } from '@/Components/catalyst/text';
import ServerScopedLayout from '@/Layout/ServerScopedLayout';
import { ArrowLeftIcon, TrashIcon } from '@heroicons/react/16/solid';
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import Form from './Partials/Form';
import { Command } from './types';

export default function Edit({ serverId, command }: { serverId: string; command: Command }) {
    return (
        <div className="max-w-2xl mx-auto flex flex-col gap-4">
            <div className="flex flex-row gap-4 justify-between">
                <Button plain href={route('server.command.index', { serverId: serverId })}>
                    <ArrowLeftIcon />
                    Go Back to Commands
                </Button>
                <DeleteDialog serverId={serverId} command={command} />
            </div>

            <Form serverId={serverId} existingCommand={command} />
        </div>
    );
}

function DeleteDialog({ serverId, command }: { serverId: string; command: Command }) {
    const [isOpen, setIsOpen] = useState<boolean>(false);

    return (
        <>
            <Head title="Edit Command" />
            <Button color="red" type="button" onClick={() => setIsOpen(true)}>
                <TrashIcon />
                Delete
            </Button>
            <Dialog open={isOpen} onClose={setIsOpen}>
                <DialogTitle>Confirm Deletion</DialogTitle>
                <DialogDescription>
                    Are you sure you want to delete the command <Strong>!{command.command}</Strong>?
                </DialogDescription>
                <DialogActions>
                    <Button plain onClick={() => setIsOpen(false)}>
                        Cancel
                    </Button>
                    <Button
                        color="red"
                        type="button"
                        href={route('server.command.destroy', { serverId: serverId, command: command.id })}
                        method="delete"
                    >
                        <TrashIcon />
                        Delete
                    </Button>
                </DialogActions>
            </Dialog>
        </>
    );
}

Edit.layout = (page: React.ReactNode) => <ServerScopedLayout>{page}</ServerScopedLayout>;
