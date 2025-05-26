import { Head, useForm } from '@inertiajs/react';

type License = {
    id: string;
    stripe_status: string;
    plan_id: string | null;
    guild_id: string | null;
    assigned_at: string | null;
    ends_at: string | null;
    created_at: string;
    updated_at: string;
};

type Props = {
    licenses: License[];
};

export default function Index({ licenses }: Props) {
    const {
        data,
        setData,
        patch,
        delete: destroy,
        processing,
        errors,
    } = useForm({
        guild_id: '',
    });

    const handleAssign = (licenseId: string) => {
        patch(route('licenses.update', licenseId), {
            preserveScroll: true,
        });
    };

    const handleUnassign = (licenseId: string) => {
        destroy(route('licenses.destroy', licenseId), {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Manage Licenses" />
            <h1>Licenses</h1>

            {licenses.length === 0 && <p>You have no licenses yet.</p>}

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Status</th>
                        <th>Guild</th>
                        <th>Assigned At</th>
                        <th>Ends At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {licenses.map((license) => (
                        <tr key={license.id}>
                            <td>{license.id.slice(0, 8)}…</td>
                            <td>{license.stripe_status}</td>
                            <td>{license.guild_id ?? <em>—</em>}</td>
                            <td>{license.assigned_at ?? <em>—</em>}</td>
                            <td>{license.ends_at ?? <em>—</em>}</td>
                            <td>
                                {license.guild_id ? (
                                    <button onClick={() => handleUnassign(license.id)} disabled={processing}>
                                        Unassign
                                    </button>
                                ) : (
                                    <>
                                        <input
                                            type="text"
                                            placeholder="Guild ID"
                                            value={data.guild_id}
                                            onChange={(e) => setData('guild_id', e.target.value)}
                                        />
                                        <button onClick={() => handleAssign(license.id)} disabled={processing}>
                                            Assign
                                        </button>
                                        {errors.guild_id && <div>{errors.guild_id}</div>}
                                    </>
                                )}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </>
    );
}
