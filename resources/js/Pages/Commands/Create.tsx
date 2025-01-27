import { Button } from '@/Components/button';
import { Checkbox, CheckboxField } from '@/Components/checkbox';
import { Field, Fieldset, Label } from '@/Components/fieldset';
import { Heading } from '@/Components/heading';
import { Input } from '@/Components/input';
import { Textarea } from '@/Components/textarea';
import { Layout } from '@/Layout/Layout';
import { useForm } from '@inertiajs/react';
import { useCallback } from 'react';
import { CommandStore } from './types';

export default function Create({guilds}: { guilds: any }) {
    console.log(guilds);
    const { data, setData, post } = useForm<CommandStore>({
        name: '',
        description: '',
        command: '',
        guild_id: null,
        is_enabled: true,
        is_public: false,
    });

    const handleSubmit = useCallback(
        (e: React.FormEvent) => {
            e.preventDefault();
            post(route('commands.store'));
        },
        [post]
    );

    return (
        <>
            <div className="mb-4 flex justify-between items-center">
                <Heading>Commands</Heading>

                <Button onClick={handleSubmit} color="green">
                    New Command
                </Button>
            </div>
            <form onSubmit={handleSubmit}>
                <Fieldset>
                    <Field>
                        <Label htmlFor="name">Name</Label>
                        <Input
                            type="text"
                            name="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                        />
                    </Field>
                    <Field>
                        <Label htmlFor="description">Description</Label>
                        <Textarea
                            name="description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                        />
                    </Field>
                    <Field>
                        <Label htmlFor="command">Command</Label>
                        <Input
                            type="text"
                            name="command"
                            value={data.command}
                            onChange={(e) => setData('command', e.target.value)}
                        />
                    </Field>
                    <CheckboxField>
                        <Label htmlFor="is_enabled">Enabled</Label>
                        <Checkbox
                            name="is_enabled"
                            checked={data.is_enabled}
                            onChange={(checked: boolean) => setData('is_enabled', checked)}
                        />
                    </CheckboxField>
                    <CheckboxField>
                        <Label htmlFor="is_public">Public</Label>
                        <Checkbox
                            name="is_public"
                            checked={data.is_public}
                            onChange={(checked: boolean) => setData('is_public', checked)}
                        />
                    </CheckboxField>
                </Fieldset>
            </form>
        </>
    );
}

Create.layout = (page: React.ReactNode) => <Layout>{page}</Layout>;
