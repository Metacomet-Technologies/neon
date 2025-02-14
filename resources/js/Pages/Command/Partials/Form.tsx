import { Button } from '@/Components/button';
import { Checkbox, CheckboxField } from '@/Components/checkbox';
import { Dialog, DialogActions, DialogBody, DialogTitle } from '@/Components/dialog';
import { Divider } from '@/Components/divider';
import { Description, ErrorMessage, Field, Fieldset, Label } from '@/Components/fieldset';
import { Input } from '@/Components/input';
import { Textarea } from '@/Components/textarea';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { useForm } from '@inertiajs/react';
import React, { useCallback, useState } from 'react';
import { ChromePicker, ColorResult } from 'react-color';
import { Command, CommandStore } from '../types';

/**
 * Form component for creating or updating a command.
 *
 * @param {Object} props - The component props.
 * @param {string} props.serverId - The ID of the server.
 * @param {Command|null} [props.existingCommand] - The existing command to edit, if any.
 * @returns {JSX.Element} The form component.
 */
export default function Form({
    serverId,
    existingCommand,
}: {
    serverId: string;
    existingCommand?: Command | null;
}): React.JSX.Element {
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
                put(route('server.command.update', { serverId, command: existingCommand.id }));
            } else {
                post(route('server.command.store', { serverId }));
            }
        },
        [post, put]
    );

    const handleTextChange = useCallback(
        (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
            setData(e.target.name, e.target.value);
        },
        [setData]
    );

    const handleCheckboxChange = useCallback(
        (name: string, checked: boolean) => {
            setData(name, checked);
        },
        [setData]
    );

    const handleIsEnabledChange = useCallback(
        (checked: boolean) => handleCheckboxChange('is_enabled', checked),
        [handleCheckboxChange]
    );
    const handleIsPublicChange = useCallback(
        (checked: boolean) => handleCheckboxChange('is_public', checked),
        [handleCheckboxChange]
    );
    const handleIsEmbedChange = useCallback(
        (checked: boolean) => handleCheckboxChange('is_embed', checked),
        [handleCheckboxChange]
    );

    return (
        <Card>
            <CardHeader>
                <CardTitle>{existingCommand ? 'Edit Command' : 'Create Command'}</CardTitle>
                <CardDescription>
                    {existingCommand
                        ? 'Edit the command details below.'
                        : 'Create a new command for Neon to respond to.'}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form onSubmit={handleSubmit}>
                    <Fieldset className="space-y-4">
                        <TextField
                            label="Command"
                            description=""
                            disabled={Boolean(existingCommand)}
                            name="command"
                            value={'!' + data.command}
                            onChange={handleTextChange}
                            error={errors.command}
                            required={!existingCommand}
                        />

                        <TextField
                            label="Response"
                            description="What should Neon say when this command is triggered?"
                            name="response"
                            value={data.response}
                            onChange={handleTextChange}
                            error={errors.response}
                            required={!data.is_embed}
                            disabled={data.is_embed}
                        />
                        <TextAreaField
                            label="Description"
                            description="What does this command do?"
                            name="description"
                            value={data.description}
                            onChange={handleTextChange}
                            error={errors.description}
                        />
                        <CheckboxFieldWrapper
                            label="Enabled"
                            description="If disabled, Neon will ignore this command."
                            name="is_enabled"
                            checked={data.is_enabled}
                            onChange={handleIsEnabledChange}
                            error={errors.is_enabled}
                        />
                        <CheckboxFieldWrapper
                            label="Public"
                            description="If disabled, this command will only work for admins."
                            name="is_public"
                            checked={data.is_public}
                            onChange={handleIsPublicChange}
                            error={errors.is_public}
                        />
                        <CheckboxFieldWrapper
                            label="Embed"
                            description="Use an embedded message for this command."
                            name="is_embed"
                            checked={data.is_embed}
                            onChange={handleIsEmbedChange}
                            error={errors.is_embed}
                        />
                        {data.is_embed && <EmbedFields data={data} setData={setData} errors={errors} />}
                    </Fieldset>
                    <div className="flex flex-row-reverse items-center mt-4">
                        <Button type="submit" color="teal">
                            Save
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}

interface ColorPickerDialogProps {
    isOpen: boolean;
    onClose: () => void;
    color: string;
    onChange: (color: ColorResult) => void;
}

const ColorPickerDialog: React.FC<ColorPickerDialogProps> = ({ isOpen, onClose, color, onChange }) => {
    return (
        <Dialog open={isOpen} onClose={onClose}>
            <DialogTitle>Pick a Color</DialogTitle>
            <DialogBody className="flex justify-center">
                <ChromePicker disableAlpha className="mx-auto" color={color} onChange={onChange} />
            </DialogBody>
            <DialogActions>
                <Button color="pink" onClick={onClose}>
                    Accept
                </Button>
            </DialogActions>
        </Dialog>
    );
};

function EmbedFields({
    data,
    setData,
    errors,
}: {
    data: CommandStore;
    setData: Function;
    errors: Partial<Record<keyof CommandStore, string>>;
}) {
    const [isColorPickerOpen, setIsColorPickerOpen] = useState<boolean>(false);

    /**
     * Opens the color picker dialog.
     */
    const handleColorPickerOpen = useCallback(() => setIsColorPickerOpen(true), []);

    /**
     * Closes the color picker dialog.
     */
    const handleColorPickerClose = useCallback(() => setIsColorPickerOpen(false), []);

    const connvertColorHexToInteger = useCallback((color: string) => parseInt(color.replace('#', ''), 16), []);
    const convertColorIntegerToHex = useCallback((color: number) => `#${color.toString(16).padStart(6, '0')}`, []);

    const handleTextChange = useCallback(
        (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
            setData(e.target.name, e.target.value);
        },
        [setData]
    );

    const handleColorChange = useCallback(
        (color: ColorResult) => {
            setData('embed_color', connvertColorHexToInteger(color.hex));
        },
        [setData]
    );

    return (
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

                <ColorPickerDialog
                    isOpen={isColorPickerOpen}
                    onClose={handleColorPickerClose}
                    color={convertColorIntegerToHex(data.embed_color || 65535)}
                    onChange={handleColorChange}
                />
                {Boolean(errors.embed_color) && <ErrorMessage>{errors.embed_color}</ErrorMessage>}
            </Field>
            <TextField
                label="Embed Title"
                description="What should the title of the embedded message be?"
                name="embed_title"
                value={data.embed_title || ''}
                onChange={handleTextChange}
                error={errors.embed_title}
                required={data.is_embed}
            />
            <TextAreaField
                label="Embed Description"
                description="What should the embedded message say?"
                name="embed_description"
                value={data.embed_description || ''}
                onChange={handleTextChange}
                error={errors.embed_description}
                required
            />
        </>
    );
}

interface TextFieldProps {
    label: string;
    description: string;
    name: string;
    value: string;
    onChange: (e: React.ChangeEvent<HTMLInputElement>) => void;
    error?: string;
    required?: boolean;
    disabled?: boolean;
}

const TextField = ({
    label,
    description,
    name,
    value,
    onChange,
    error,
    required = false,
    disabled,
}: TextFieldProps) => (
    <Field>
        <Label htmlFor={name}>
            {label} {required && <span className="text-red-500">*</span>}
        </Label>
        <Description>{description}</Description>
        <Input type="text" name={name} value={value} onChange={onChange} invalid={Boolean(error)} disabled={disabled} />
        {Boolean(error) && <ErrorMessage>{error}</ErrorMessage>}
    </Field>
);

interface TextAreaFieldProps {
    label: string;
    description: string;
    name: string;
    value: string;
    onChange: (e: React.ChangeEvent<HTMLTextAreaElement>) => void;
    error?: string;
    required?: boolean;
}

const TextAreaField = ({ label, description, name, value, onChange, error, required }: TextAreaFieldProps) => (
    <Field>
        <Label htmlFor={name}>
            {label} {required && <span className="text-red-500">*</span>}
        </Label>
        <Description>{description}</Description>
        <Textarea name={name} value={value} rows={3} onChange={onChange} invalid={Boolean(error)} />
        {Boolean(error) && <ErrorMessage>{error}</ErrorMessage>}
    </Field>
);

interface CheckboxFieldWrapperProps {
    label: string;
    description: string;
    name: string;
    checked: boolean;
    onChange: (checked: boolean) => void;
    error?: string;
}

const CheckboxFieldWrapper = ({ label, description, name, checked, onChange, error }: CheckboxFieldWrapperProps) => (
    <CheckboxField>
        <Checkbox name={name} checked={checked} onChange={onChange} />
        <Label htmlFor={name}>
            {label} <span className="text-red-500">*</span>
        </Label>
        <Description>{description}</Description>
        {Boolean(error) && <ErrorMessage>{error}</ErrorMessage>}
    </CheckboxField>
);
