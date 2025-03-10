import { Description, Label } from '@/Components/fieldset';
import { Switch, SwitchField } from '@/Components/switch';
import { Textarea } from '@/Components/textarea';
import { Card, CardContent, CardFooter, CardHeader } from '@/Components/ui/card';
import { useForm } from '@inertiajs/react';
import { JSX, useState } from 'react';

export default function WelcomeSetting(): JSX.Element {
    const [isActive, setIsActive] = useState<boolean>(false);
    const { data, setData } = useForm({
        message: '',
        channel_id: '',
    });
    return (
        <Card>
            <CardHeader>
                <SwitchField>
                    <Label>Custom Welcome Message</Label>
                    <Description>Configure the welcome message for your server.</Description>
                    <Switch color="pink" name="is_active" />
                </SwitchField>
            </CardHeader>
            <CardContent>
                {/* Listbox of Server's Channels (text) */}
                <Textarea name="message" placeholder="Welcome to the server!" />
                {/* Button to Save (button) */}
            </CardContent>
            <CardFooter></CardFooter>
        </Card>
    );
}
