import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { FieldErrors, WorkspaceNav } from '@/components/workspace-ui';
import type { Workspace } from '@/types/platform';
export default function Configuration({
    workspace,
    configuration,
}: {
    workspace: Workspace;
    configuration: {
        version: number;
        terminology: { lead: string; customer: string };
    };
}) {
    const form = useForm({
        version: configuration.version,
        terminology: configuration.terminology,
    });
    return (
        <div className="space-y-6 p-6">
            <Head title="Configuration" />
            <h1 className="text-2xl font-semibold">
                Configure {workspace.name}
            </h1>
            <WorkspaceNav workspace={workspace} canConfigure />
            <p>
                Published version: {configuration.version}. Changes are saved as
                a new immutable version.
            </p>
            <form
                className="max-w-lg space-y-4"
                onSubmit={(e) => {
                    e.preventDefault();
                    form.post(
                        '/workspaces/' + workspace.id + '/configuration',
                        { onSuccess: () => routerReload() },
                    );
                }}
            >
                {(['lead', 'customer'] as const).map((key) => (
                    <label className="block capitalize" key={key}>
                        {key} label
                        <Input
                            maxLength={40}
                            required
                            value={form.data.terminology[key]}
                            onChange={(e) =>
                                form.setData('terminology', {
                                    ...form.data.terminology,
                                    [key]: e.target.value,
                                })
                            }
                        />
                    </label>
                ))}
                <FieldErrors errors={form.errors} />
                <Button disabled={form.processing}>
                    Publish configuration
                </Button>
            </form>
            <p className="text-sm text-muted-foreground">
                Customer label is stored for future customer screens; this
                milestone applies the lead label to CRM. Layout, custom fields
                and workflow builders come later.
            </p>
        </div>
    );
}
function routerReload() {
    window.location.reload();
}
Configuration.layout = {
    breadcrumbs: [{ title: 'Workspaces', href: '/dashboard' }],
};
