import { Heading, Subheading } from '@/Components/heading';
import { Layout } from '@/Layout/Layout';
import Form from './Partials/Form';
import { Command } from './types';

export default function Edit({ serverId, command }: { serverId: string; command: Command }) {
    return (
        <div className="max-w-2xl mx-auto">
            <Heading>Edit a New Command</Heading>
            <Subheading>See what you can do with Neon.</Subheading>
            <Form serverId={serverId} existingCommand={command} />
        </div>
    );
}

Edit.layout = (page: React.ReactNode) => <Layout>{page}</Layout>;
