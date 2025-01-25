import { Heading } from '@/Components/heading';
import { Text } from '@/Components/text';

export default function Unsubscribe() {
    return (
        <div className="flex flex-col items-center justify-center min-h-screen py-6">
            <div className="w-full max-w-md px-6">
                <Heading>Sorry to see you go!</Heading>
                <Text>You have been successfully unsubscribed from our mailing list.</Text>
            </div>
        </div>
    );
}
