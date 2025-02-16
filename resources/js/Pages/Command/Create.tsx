import { Button } from '@/Components/button';
import ServerScopedLayout from '@/Layout/ServerScopedLayout';
import { ArrowLeftIcon } from '@heroicons/react/16/solid';
import { Head } from '@inertiajs/react';
import Form from './Partials/Form';

export default function Create({ serverId }: { serverId: string }) {
    return (
        <>
            <Head title="Create Command" />
            <div className="max-w-2xl mx-auto flex flex-col gap-4">
                <div className="flex flex-row items-center gap-2">
                    <Button plain href={route('server.command.index', { serverId: serverId })}>
                        <ArrowLeftIcon />
                        Go Back to Commands
                    </Button>
                </div>

                <Form serverId={serverId} existingCommand={null} />
            </div>
        </>
    );
}

Create.layout = (page: React.ReactNode) => <ServerScopedLayout>{page}</ServerScopedLayout>;
