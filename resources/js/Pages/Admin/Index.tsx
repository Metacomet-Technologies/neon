import { Heading, Subheading } from '@/Components/heading';
import AdminLayout from '@/Layout/AdminLayout';
import { Head } from '@inertiajs/react';

export default function Index() {
    return (
        <>
            <Head title="Admin" />

            <Heading>Admin</Heading>

            <Subheading>Welcome to the admin area.</Subheading>
        </>
    );
}

Index.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
