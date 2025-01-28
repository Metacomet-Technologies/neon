import { Button } from '@/Components/button';
import { Heading, Subheading } from '@/Components/heading';
import { Layout } from '@/Layout/Layout';
import { ArrowLeftIcon } from '@heroicons/react/16/solid';
import { Head } from '@inertiajs/react';
import Form from './Partials/Form';

export default function Create({ serverId }: { serverId: string }) {
    return (
        <>
            <Head title="Create Command" />
            <div className="max-w-2xl mx-auto flex flex-col gap-4">
                <div className="flex flex-row justify-between items-center gap-4">
                    <div>
                        <Heading>Create a New Command</Heading>
                        <Subheading>See what you can do with Neon.</Subheading>
                    </div>
                    <div className="flex flex-row gap-4">
                        <Button plain href={route('server.command.index', { serverId: serverId })}>
                            <ArrowLeftIcon />
                            Go Back to Commands
                        </Button>
                    </div>
                </div>

                <Form serverId={serverId} existingCommand={null} />
            </div>
        </>
    );
}

Create.layout = (page: React.ReactNode) => <Layout>{page}</Layout>;
