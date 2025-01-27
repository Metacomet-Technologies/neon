import { Button } from '@/Components/button';
import { Checkbox, CheckboxField } from '@/Components/checkbox';
import { Description, ErrorMessage, Field, Fieldset, Label } from '@/Components/fieldset';
import { Input } from '@/Components/input';
import { Textarea } from '@/Components/textarea';
import { useForm } from '@inertiajs/react';
import { useCallback } from 'react';
import { Command, CommandStore } from '../types';

export default function Form({ serverId, existingCommand }: { serverId: string; existingCommand?: Command | null }) {
    const { data, setData, post, put, errors } = useForm<CommandStore>({
        command: existingCommand?.command || '',
        description: existingCommand?.description || '',
        response: existingCommand?.response || '',
        is_enabled: existingCommand?.is_enabled || true,
        is_public: existingCommand?.is_public || true,
    });

    const handleSubmit = useCallback(
        (e: React.FormEvent) => {
            e.preventDefault();
            if (existingCommand) {
                put(route('server.command.update', { serverId: serverId, command: existingCommand.id }));
            } else {
                post(route('server.command.store', { serverId: serverId }));
            }
        },
        [post, put]
    );

    return (
        <form
            onSubmit={handleSubmit}
            className="bg-zinc-100 dark:bg-zinc-800 p-4 rounded-lg border border-zinc-200 dark:border-zinc-700 shadow"
        >
            <Fieldset className="space-y-4">
                <Field>
                    <Label htmlFor="command">
                        Command <span className="text-red-500">*</span>
                    </Label>
                    <Description>The ! prefix will automatically be appended.</Description>
                    <Input
                        type="text"
                        name="command"
                        invalid={!!errors.command}
                        value={data.command}
                        onChange={(e) => setData('command', e.target.value)}
                    />
                    {!!errors.command && <ErrorMessage>{errors.command}</ErrorMessage>}
                </Field>
                <Field>
                    <Label htmlFor="description">Description</Label>
                    <Description>What does this command do?</Description>
                    <Textarea
                        name="description"
                        value={data.description}
                        rows={3}
                        invalid={!!errors.description}
                        onChange={(e) => setData('description', e.target.value)}
                    />
                    {!!errors.description && <ErrorMessage>{errors.description}</ErrorMessage>}
                </Field>
                <Field>
                    <Label htmlFor="response">
                        Response <span className="text-red-500">*</span>
                    </Label>
                    <Description>What should Neon say when this command is triggered?</Description>
                    <Textarea
                        name="response"
                        invalid={!!errors.response}
                        rows={1}
                        value={data.response}
                        onChange={(e) => setData('response', e.target.value)}
                    />
                    {!!errors.response && <ErrorMessage>{errors.response}</ErrorMessage>}
                </Field>
                <CheckboxField>
                    <Checkbox
                        name="is_enabled"
                        checked={data.is_enabled}
                        onChange={(checked: boolean) => setData('is_enabled', checked)}
                    />
                    <Label htmlFor="is_enabled">
                        Enabled <span className="text-red-500">*</span>
                    </Label>
                    <Description>If disabled, Neon will ignore this command.</Description>
                    {!!errors.is_enabled && <ErrorMessage>{errors.is_enabled}</ErrorMessage>}
                </CheckboxField>
                <CheckboxField>
                    <Checkbox
                        name="is_public"
                        checked={data.is_public}
                        onChange={(checked: boolean) => setData('is_public', checked)}
                    />
                    <Label htmlFor="is_public">
                        Public <span className="text-red-500">*</span>
                    </Label>
                    <Description>If disabled, this command will only work for admins.</Description>
                    {!!errors.is_public && <ErrorMessage>{errors.is_public}</ErrorMessage>}
                </CheckboxField>
            </Fieldset>
            <div className="flex flex-row-reverse items-center mt-4">
                <Button type="submit" color="teal">
                    Save
                </Button>
            </div>
        </form>
    );
}
