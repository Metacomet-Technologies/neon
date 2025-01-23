import { Heading } from '@/Components/heading';
import { Text } from '@/Components/text';
import { Layout } from '@/Layout/Layout';
import React from 'react';

/**
 * Home page component.
 *
 * @return {React.JSX.Element} Home page.
 */
export default function Home(): React.JSX.Element {
    return (
        <div>
            <Heading level={1}>Home Page</Heading>
            <Text>
                Welcome to your new Inertia application! This starter template is a starting point for building
                full-stack React applications using Inertia.js.
            </Text>
        </div>
    );
}

Home.layout = (page: React.ReactNode) => <Layout>{page}</Layout>;
