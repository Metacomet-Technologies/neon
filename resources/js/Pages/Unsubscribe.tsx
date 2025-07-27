import { Heading } from '@/Components/catalyst/heading';
import { Text } from '@/Components/catalyst/text';
import { Head } from '@inertiajs/react';
import React from 'react';

/**
 * Unsubscribe component renders the unsubscribe confirmation message.
 *
 * @returns {JSX.Element} The JSX code for the unsubscribe page.
 */
export default function Unsubscribe(): React.JSX.Element {
    return (
        <>
            <Head title="Unsubscribe from Mailing List" />
            <div className="flex flex-col items-center justify-center min-h-screen py-6">
                <div className="w-full max-w-md px-6">
                    <Heading>Sorry to see you go!</Heading>
                    <Text>You have been successfully unsubscribed from our mailing list.</Text>
                </div>
            </div>
        </>
    );
}
