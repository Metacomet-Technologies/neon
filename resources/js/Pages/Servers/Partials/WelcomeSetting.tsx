import { Button } from '@/Components/catalyst/button';
import { Combobox, ComboboxLabel, ComboboxOption } from '@/Components/catalyst/combobox';
import { Description, Field, FieldGroup, Label } from '@/Components/catalyst/fieldset';
import { Switch, SwitchField } from '@/Components/catalyst/switch';
import { Textarea } from '@/Components/catalyst/textarea';
import { Card, CardContent, CardFooter, CardHeader } from '@/Components/ui/card';
import type { Guild, WelcomeSetting } from '@/types';
import { PageProps } from '@/types';
import { useForm, usePage } from '@inertiajs/react';
import { JSX } from 'react';

export default function WelcomeSetting({
    channels,
    existingSetting,
}: {
    channels: { id: string; name: string }[];
    existingSetting?: WelcomeSetting;
}): JSX.Element {
    const { auth } = usePage<PageProps>().props;

    const guild = auth.user?.guilds?.find((guild: Guild) => guild.id === auth.user?.current_server_id);
    const serverId = guild?.id;

    function getChannelFromId(id: string) {
        return channels.find((channel) => channel.id === id);
    }

    const { data, setData, post, processing } = useForm<{
        message: string;
        channel: { id: string; name: string };
        is_active: boolean;
    }>({
        message: existingSetting?.message || '{user} has joined the server!',
        channel: getChannelFromId(existingSetting?.channel_id || '') || channels[0],
        is_active: existingSetting?.is_active || false,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('server.settings.welcome.save', { serverId: serverId }));
    };

    return (
        <form onSubmit={handleSubmit}>
            <Card>
                <CardHeader>
                    <SwitchField>
                        <Label>Custom Welcome Message</Label>
                        <Description>Configure the welcome message for your server.</Description>
                        <Switch
                            color="pink"
                            name="is_active"
                            checked={data.is_active}
                            onChange={(checked: boolean) => setData('is_active', checked)}
                        />
                    </SwitchField>
                </CardHeader>
                <CardContent>
                    <FieldGroup>
                        <Field>
                            <Label>Channel</Label>
                            <Combobox
                                name="user"
                                options={channels}
                                displayValue={(channel) => channel?.name}
                                onChange={(channel) => setData('channel', channel as { id: string; name: string })}
                                value={data.channel}
                                disabled={!data.is_active}
                            >
                                {(user) => (
                                    <ComboboxOption value={user}>
                                        <ComboboxLabel>{user.name}</ComboboxLabel>
                                    </ComboboxOption>
                                )}
                            </Combobox>
                        </Field>
                        <Field>
                            <Label>Message Content</Label>
                            <Textarea
                                name="message"
                                placeholder="Welcome to the server!"
                                value={data.message}
                                onChange={(e) => setData('message', e.target.value)}
                                disabled={!data.is_active}
                            />
                        </Field>
                    </FieldGroup>
                </CardContent>
                <CardFooter className="flex justify-end">
                    <Button color="pink" type="submit" disabled={processing}>
                        Save
                    </Button>
                </CardFooter>
            </Card>
        </form>
    );
}
