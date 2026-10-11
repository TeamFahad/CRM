import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { FieldErrors, WorkspaceNav } from '@/components/workspace-ui';
import { translator } from '@/lib/platform-i18n';
import type { Pagination, Workspace } from '@/types/platform';

type Member = { id: number; name: string; email: string; role: string };
type Invitation = {
    id: string;
    email: string;
    role: string;
    status: string;
    expires_at: string;
};

function Pages({ links }: { links: Pagination<unknown>['links'] }) {
    return (
        <nav aria-label="Pagination" className="flex flex-wrap gap-3 py-3">
            {links.map((link, index) =>
                link.url ? (
                    <Link
                        key={index}
                        className={link.active ? 'font-bold underline' : ''}
                        href={link.url}
                        preserveScroll
                    >
                        {link.label
                            .replace(/&laquo;/g, '‹')
                            .replace(/&raquo;/g, '›')
                            .replace(/<[^>]+>/g, '')}
                    </Link>
                ) : null,
            )}
        </nav>
    );
}

function MemberCard({
    member,
    roles,
    base,
    bn,
}: {
    member: Member;
    roles: string[];
    base: string;
    bn: boolean;
}) {
    const form = useForm({ role: member.role, current_role: member.role });
    const [confirmRemove, setConfirmRemove] = useState(false);
    return (
        <section className="space-y-3 rounded-xl border p-4">
            <h3 className="font-semibold">{member.name}</h3>
            <p className="text-sm break-all">{member.email}</p>
            {member.role === 'owner' ? (
                <p>
                    {bn
                        ? 'মালিক — এই জায়গা থেকে বাদ দেওয়া বা role বদলানো যাবে না।'
                        : 'Owner — protected from removal or role changes here.'}
                </p>
            ) : (
                <>
                    <form
                        className="flex flex-wrap gap-3"
                        onSubmit={(e) => {
                            e.preventDefault();
                            form.patch(base + '/members/' + member.id, {
                                preserveScroll: true,
                            });
                        }}
                    >
                        <select
                            aria-label={'Role for ' + member.name}
                            className="rounded border bg-background p-2"
                            value={form.data.role}
                            onChange={(e) =>
                                form.setData('role', e.target.value)
                            }
                        >
                            {roles.map((role) => (
                                <option key={role} value={role}>
                                    {role}
                                </option>
                            ))}
                        </select>
                        <Button disabled={form.processing}>
                            {bn ? 'অনুমতি সংরক্ষণ' : 'Save role'}
                        </Button>
                    </form>
                    {confirmRemove ? (
                        <div className="space-y-2">
                            <p role="alert">
                                {bn
                                    ? 'এই কর্মীকে বাদ দিলে তিনি আর এই কোম্পানিতে ঢুকতে পারবেন না। নিশ্চিত?'
                                    : 'This member will lose access to this business. Confirm removal?'}
                            </p>
                            <div className="flex gap-3">
                                <Button
                                    variant="destructive"
                                    disabled={form.processing}
                                    onClick={() =>
                                        form.delete(
                                            base + '/members/' + member.id,
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    {bn ? 'বাদ দিন' : 'Confirm removal'}
                                </Button>
                                <Button
                                    variant="outline"
                                    onClick={() => setConfirmRemove(false)}
                                >
                                    {bn ? 'বাতিল' : 'Cancel'}
                                </Button>
                            </div>
                        </div>
                    ) : (
                        <Button
                            variant="outline"
                            disabled={form.processing}
                            onClick={() => setConfirmRemove(true)}
                        >
                            {bn ? 'কর্মীকে বাদ দিন' : 'Remove member'}
                        </Button>
                    )}
                </>
            )}
            <FieldErrors errors={form.errors} />
        </section>
    );
}

export default function Team({
    workspace,
    members,
    invitations,
    roles,
}: {
    workspace: Workspace;
    members: Pagination<Member>;
    invitations: Pagination<Invitation>;
    roles: string[];
}) {
    const bn = workspace.locale === 'bn';
    const t = translator(workspace.locale);
    const base = '/workspaces/' + workspace.id + '/team';
    const form = useForm({ email: '', role: 'agent' });
    const { flash, props } = usePage();
    const invitationLink =
        typeof flash.invitationLink === 'string' ? flash.invitationLink : '';
    const [copied, setCopied] = useState(false);
    const [revoking, setRevoking] = useState<string | null>(null);
    return (
        <div className="space-y-6 p-6" lang={workspace.locale}>
            <Head title={t('team')} />
            <h1 className="text-2xl font-semibold">
                {t('team')} · {workspace.name}
            </h1>
            <WorkspaceNav workspace={workspace} canConfigure />
            <section className="space-y-3 rounded-xl border p-5">
                <h2 className="text-lg font-semibold">
                    {bn ? 'কে কী করতে পারবেন?' : 'What can each role do?'}
                </h2>
                <p>
                    {bn
                        ? 'Manager ও Agent: লিড দেখা, যোগ করা এবং status পরিবর্তন। Auditor: শুধু লিড দেখা। শুধু মালিক কর্মী ও ব্যবসার settings পরিচালনা করবেন।'
                        : 'Manager and Agent can view/create leads and change status. Auditor can only view leads. Only owners manage team members and business settings.'}
                </p>
                <p className="text-sm text-muted-foreground">
                    {bn
                        ? 'এই ফেজে আলাদা কর্মীর জন্য আলাদা lead assignment এখনো নেই।'
                        : 'Per-agent lead assignment is not implemented in this phase.'}
                </p>
            </section>
            <form
                className="max-w-xl space-y-4 rounded-xl border p-5"
                onSubmit={(e) => {
                    e.preventDefault();
                    form.post(base + '/invitations', {
                        preserveScroll: true,
                        onSuccess: () => {
                            form.reset('email');
                            setCopied(false);
                        },
                    });
                }}
            >
                <h2 className="text-lg font-semibold">
                    {bn ? 'নতুন কর্মীকে invitation দিন' : 'Invite a team member'}
                </h2>
                <label className="block">
                    {t('email')}
                    <Input
                        type="email"
                        required
                        maxLength={254}
                        value={form.data.email}
                        onChange={(e) => form.setData('email', e.target.value)}
                    />
                </label>
                <label className="block">
                    {bn ? 'কাজের অনুমতি' : 'Role'}
                    <select
                        className="block w-full rounded border bg-background p-2"
                        value={form.data.role}
                        onChange={(e) => form.setData('role', e.target.value)}
                    >
                        {roles.map((role) => (
                            <option key={role} value={role}>
                                {role}
                            </option>
                        ))}
                    </select>
                </label>
                <FieldErrors errors={form.errors} />
                <Button disabled={form.processing}>
                    {bn ? 'Invitation link তৈরি করুন' : 'Create invitation link'}
                </Button>
                <p className="text-sm text-muted-foreground">
                    {bn
                        ? 'লিংক ৭২ ঘণ্টা চলবে। কর্মীকে নিরাপদভাবে শেয়ার করুন। এই ফেজে স্বয়ংক্রিয় ইমেইল পাঠানো হয় না।'
                        : 'Valid for 72 hours. Share securely with the recipient. Automatic email delivery is not enabled in this phase.'}
                </p>
            </form>
            {invitationLink && (
                <section
                    className="space-y-3 rounded-xl border p-5"
                    role="status"
                >
                    <h2 className="font-semibold">
                        {bn ? 'এই লিংকটি এখন কপি করুন' : 'Copy this link now'}
                    </h2>
                    <Input
                        aria-label="Invitation link"
                        readOnly
                        value={invitationLink}
                        onFocus={(e) => e.target.select()}
                    />
                    <Button
                        variant="outline"
                        onClick={() => {
                            void navigator.clipboard
                                .writeText(invitationLink)
                                .then(() => setCopied(true))
                                .catch(() => setCopied(false));
                        }}
                    >
                        {copied
                            ? bn
                                ? 'কপি হয়েছে'
                                : 'Copied'
                            : bn
                              ? 'কপি করুন'
                              : 'Copy link'}
                    </Button>
                    <p>
                        {bn
                            ? 'কর্মী নির্দিষ্ট ইমেইল দিয়ে account খুলে login ও email verification করবেন, তারপর মূল লিংকটি আবার খুলবেন। লিংকটি আর পরে দেখা যাবে না; দরকার হলে নতুন invitation দিন।'
                            : 'Recipient must register/sign in and verify the specified email, then reopen the original link. This link is shown once; reissue if needed.'}
                    </p>
                </section>
            )}
            <FieldErrors errors={props.errors} />
            <h2 className="text-xl font-semibold">
                {bn ? 'বর্তমান কর্মী' : 'Current members'} ({members.total})
            </h2>
            <div className="grid gap-4 lg:grid-cols-2">
                {members.data.map((member) => (
                    <MemberCard
                        key={member.id + ':' + member.role}
                        member={member}
                        roles={roles}
                        base={base}
                        bn={bn}
                    />
                ))}
            </div>
            <Pages links={members.links} />
            <h2 className="text-xl font-semibold">
                {bn ? 'Invitation history' : 'Invitation history'} (
                {invitations.total})
            </h2>
            <div className="overflow-auto rounded-xl border">
                <table className="w-full text-left text-sm">
                    <thead>
                        <tr className="border-b">
                            <th className="p-3">{t('email')}</th>
                            <th className="p-3">Role</th>
                            <th className="p-3">{t('status')}</th>
                            <th className="p-3">
                                {bn ? 'মেয়াদ শেষ' : 'Expires'}
                            </th>
                            <th className="p-3">{bn ? 'কাজ' : 'Action'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {invitations.data.map((invitation) => (
                            <tr key={invitation.id} className="border-b">
                                <td className="p-3">{invitation.email}</td>
                                <td className="p-3">{invitation.role}</td>
                                <td className="p-3">{invitation.status}</td>
                                <td className="p-3">
                                    {invitation.expires_at} UTC
                                </td>
                                <td className="p-3">
                                    {invitation.status === 'pending' && (
                                        <Button
                                            variant="outline"
                                            disabled={revoking !== null}
                                            onClick={() => {
                                                setRevoking(invitation.id);
                                                router.delete(
                                                    base +
                                                        '/invitations/' +
                                                        invitation.id,
                                                    {
                                                        preserveScroll: true,
                                                        onFinish: () =>
                                                            setRevoking(null),
                                                    },
                                                );
                                            }}
                                        >
                                            {bn ? 'লিংক বাতিল' : 'Revoke link'}
                                        </Button>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
                {invitations.data.length === 0 && (
                    <p className="p-4">
                        {bn ? 'কোনো invitation নেই।' : 'No invitations yet.'}
                    </p>
                )}
            </div>
            <Pages links={invitations.links} />
        </div>
    );
}
Team.layout = { breadcrumbs: [{ title: 'Workspaces', href: '/dashboard' }] };
