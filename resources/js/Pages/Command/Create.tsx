import { Heading, Subheading } from '@/Components/heading';
import { Layout } from '@/Layout/Layout';
import Form from './Partials/Form';

export default function Create({ serverId }: { serverId: string }) {
    return (
        <div className="max-w-2xl mx-auto">
            <Heading>Create a New Command</Heading>
            <Subheading>See what you can do with Neon.</Subheading>
            <Form serverId={serverId} existingCommand={null} />
        </div>
    );
}

Create.layout = (page: React.ReactNode) => <Layout>{page}</Layout>;
