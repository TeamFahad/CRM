import { Head, Link } from '@inertiajs/react';
import { WorkspaceNav } from '@/components/workspace-ui';
import { translator } from '@/lib/platform-i18n';
import type { Workspace } from '@/types/platform';
export default function Show({
    workspace,
    metrics,
    modules,
    canConfigure,
}: {
    workspace: Workspace;
    metrics: Record<string, number>;
    modules: Record<string, { name: string; status: string }>;
    canConfigure: boolean;
}) {
    const t = translator(workspace.locale);
    return (
        <div className="space-y-6 p-6" lang={workspace.locale}>
            <Head title={workspace.name} />
            <h1 className="text-2xl font-semibold">{workspace.name}</h1>
            <WorkspaceNav workspace={workspace} canConfigure={canConfigure} />
            <div className="grid gap-4 md:grid-cols-3">
                {Object.entries(metrics).map(([key, value]) => (
                    <section className="rounded-xl border p-5" key={key}>
                        <h2>{t(key)}</h2>
                        <p className="text-3xl font-bold">{value}</p>
                    </section>
                ))}
            </div>
            <Link
                className="inline-block rounded border p-3"
                href={'/workspaces/' + workspace.id + '/leads'}
            >
                {t('manageLeads')}
            </Link>
            <h2 className="text-xl font-semibold">{t('modules')}</h2>
            <div className="grid gap-3 md:grid-cols-3">
                {Object.entries(modules).map(([key, module]) => (
                    <section className="rounded border p-4" key={key}>
                        <h3>{module.name}</h3>
                        <p className="text-sm text-muted-foreground">
                            {module.status === 'available'
                                ? t('available')
                                : t('planned')}
                        </p>
                    </section>
                ))}
            </div>
        </div>
    );
}
Show.layout = { breadcrumbs: [{ title: 'Workspaces', href: '/dashboard' }] };
