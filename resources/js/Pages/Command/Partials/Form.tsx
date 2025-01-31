import { Button } from '@/Components/button';
import { Checkbox, CheckboxField } from '@/Components/checkbox';
import { Dialog, DialogActions, DialogBody, DialogTitle } from '@/Components/dialog';
import { Divider } from '@/Components/divider';
import { Description, ErrorMessage, Field, Fieldset, Label } from '@/Components/fieldset';
import { Input } from '@/Components/input';
import { Textarea } from '@/Components/textarea';
import { useForm } from '@inertiajs/react';
import { useCallback, useState } from 'react';
import { ChromePicker } from 'react-color';
import { Command, CommandStore } from '../types';

export default function Form({ serverId, existingCommand }: { serverId: string; existingCommand?: Command | null }) {
    const { data, setData, post, put, errors } = useForm<CommandStore>({
        command: existingCommand?.command || '',
        description: existingCommand?.description || '',
        response: existingCommand?.response || '',
        is_enabled: existingCommand?.is_enabled || true,
        is_public: existingCommand?.is_public || true,
        is_embed: existingCommand?.is_embed || false,
        embed_color: existingCommand?.embed_color || null,
        embed_title: existingCommand?.embed_title || null,
        embed_description: existingCommand?.embed_description || null,
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

    const [isColorPickerOpen, setIsColorPickerOpen] = useState(false);

    const handleColorPickerOpen = () => setIsColorPickerOpen(true);
    const handleColorPickerClose = () => setIsColorPickerOpen(false);

    const connvertColorHexToInteger = useCallback((color: string) => parseInt(color.replace('#', ''), 16), []);
    const convertColorIntegerToHex = useCallback((color: number) => `#${color.toString(16).padStart(6, '0')}`, []);

    return (
        <form
            onSubmit={handleSubmit}
            className="bg-zinc-100 dark:bg-zinc-800 p-4 rounded-lg border border-zinc-200 dark:border-zinc-700 shadow"
        >
            <Fieldset className="space-y-4">
                {!existingCommand && (
                    <Field>
                        <Label htmlFor="command">
                            Command <span className="text-red-500">*</span>
                        </Label>
                        <Description>The ! prefix will automatically be appended.</Description>
                        <Input
                            type="text"
                            disabled={!!existingCommand}
                            name="command"
                            invalid={!!errors.command}
                            value={data.command}
                            onChange={(e) => setData('command', e.target.value)}
                        />
                        {!!errors.command && <ErrorMessage>{errors.command}</ErrorMessage>}
                    </Field>
                )}

                <Field>
                    <Label htmlFor="response">
                        Response{' '}
                        {data.is_embed ? '(Plain text will be ignored)' : <span className="text-red-500">*</span>}
                    </Label>
                    <Description>What should Neon say when this command is triggered?</Description>
                    <Textarea
                        name="response"
                        invalid={!!errors.response}
                        disabled={data.is_embed}
                        rows={1}
                        value={data.response}
                        onChange={(e) => setData('response', e.target.value)}
                    />
                    {!!errors.response && <ErrorMessage>{errors.response}</ErrorMessage>}
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
                <CheckboxField>
                    <Checkbox
                        name="is_embed"
                        checked={data.is_embed}
                        onChange={(checked: boolean) => setData('is_embed', checked)}
                    />
                    <Label htmlFor="is_embed">
                        Use Embedded Message <span className="text-red-500">*</span>
                    </Label>
                    <Description>
                        Plain text responses are boring. Enable this to use a fancy embedded message.
                    </Description>
                    {!!errors.is_embed && <ErrorMessage>{errors.is_embed}</ErrorMessage>}
                </CheckboxField>
                {data.is_embed && (
                    <>
                        <Divider />
                        <Field>
                            <Label htmlFor="embed_color">
                                Embed Color {data.is_embed && <span className="text-red-500">*</span>}
                            </Label>
                            <Description>Choose a color for the embedded message.</Description>

                            <button
                                type="button"
                                onClick={handleColorPickerOpen}
                                className="size-10 rounded-full mt-2 cursor-pointer border border-zinc-200 dark:border-zinc-700"
                                style={{ backgroundColor: convertColorIntegerToHex(data.embed_color || 65535) }}
                            />

                            <Dialog open={isColorPickerOpen} onClose={handleColorPickerClose}>
                                <DialogTitle>Pick a Color</DialogTitle>
                                <DialogBody className="flex justify-center">
                                    <ChromePicker
                                        disableAlpha
                                        className="mx-auto"
                                        color={convertColorIntegerToHex(data.embed_color || 0x000000)}
                                        onChange={(color) =>
                                            setData('embed_color', connvertColorHexToInteger(color.hex))
                                        }
                                    />
                                </DialogBody>
                                <DialogActions>
                                    <Button color="pink" onClick={handleColorPickerClose}>
                                        Accept
                                    </Button>
                                </DialogActions>
                            </Dialog>
                            {!!errors.embed_color && <ErrorMessage>{errors.embed_color}</ErrorMessage>}
                        </Field>
                        <Field>
                            <Label htmlFor="embed_title">
                                Embed Title {data.is_embed && <span className="text-red-500">*</span>}
                            </Label>
                            <Description>What should the title of the embedded message be?</Description>
                            <Input
                                type="text"
                                name="embed_title"
                                value={data.embed_title || ''}
                                onChange={(e) => setData('embed_title', e.target.value)}
                            />
                            {!!errors.embed_title && <ErrorMessage>{errors.embed_title}</ErrorMessage>}
                        </Field>
                        <Field>
                            <Label htmlFor="embed_description">
                                Embed Description {data.is_embed && <span className="text-red-500">*</span>}
                            </Label>
                            <Description>What should the embedded message say?</Description>
                            <Textarea
                                name="embed_description"
                                value={data.embed_description || ''}
                                rows={3}
                                onChange={(e) => setData('embed_description', e.target.value)}
                            />
                            {!!errors.embed_description && <ErrorMessage>{errors.embed_description}</ErrorMessage>}
                        </Field>
                    </>
                )}
            </Fieldset>
            <div className="flex flex-row-reverse items-center mt-4">
                <Button type="submit" color="teal">
                    Save
                </Button>
            </div>
        </form>
    );
}
