import { Head, Link, useForm } from '@inertiajs/react';
import { FieldErrors } from '@/components/workspace-ui';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Workspace } from '@/types/platform';

export default function Dashboard({
    workspaces,
}: {
    workspaces: (Workspace & { role: string })[];
}) {
    const form = useForm({ name: '', industry: 'service', locale: 'bn' });
    return (
        <div className="space-y-6 p-6">
            <Head title="Workspaces" />
            <h1 className="text-2xl font-semibold">
                Your businesses / আপনার ব্যবসা
            </h1>
            <p>Each workspace keeps its customer data separate.</p>
            <div className="grid gap-4 md:grid-cols-3">
                {workspaces.map((w) => (
                    <Link
                        className="rounded-xl border p-5 hover:bg-muted"
                        key={w.id}
                        href={'/workspaces/' + w.id}
                    >
                        <h2 className="font-semibold">{w.name}</h2>
                        <p>
                            {w.industry} · {w.currency} · {w.role}
                        </p>
                    </Link>
                ))}
            </div>
            {workspaces.length === 0 && (
                <p>No workspace yet. Create your first business below.</p>
            )}
            <form
                className="max-w-lg space-y-4 rounded-xl border p-5"
                onSubmit={(e) => {
                    e.preventDefault();
                    form.post('/workspaces');
                }}
            >
                <h2 className="text-lg font-semibold">
                    Create a business workspace
                </h2>
                <label className="block">
                    Business name
                    <Input
                        required
                        maxLength={100}
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                    />
                </label>
                <label className="block">
                    Industry
                    <select
                        className="block w-full rounded border bg-background p-2"
                        value={form.data.industry}
                        onChange={(e) =>
                            form.setData('industry', e.target.value)
                        }
                    >
                        {[
                            'service',
                            'clinic',
                            'retail',
                            'distribution',
                            'manufacturing',
                            'education',
                        ].map((v) => (
                            <option key={v}>{v}</option>
                        ))}
                    </select>
                </label>
                <label className="block">
                    Language
                    <select
                        className="block w-full rounded border bg-background p-2"
                        value={form.data.locale}
                        onChange={(e) => form.setData('locale', e.target.value)}
                    >
                        <option value="bn">বাংলা</option>
                        <option value="en">English</option>
                    </select>
                </label>
                <FieldErrors errors={form.errors} />
                <Button disabled={form.processing}>Create workspace</Button>
                <p className="text-sm text-muted-foreground">
                    Industry selection records your business type;
                    industry-specific workflows are planned, not enabled yet.
                </p>
            </form>
        </div>
    );
}
Dashboard.layout = {
    breadcrumbs: [{ title: 'Workspaces', href: '/dashboard' }],
};
