import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { FieldErrors } from '@/components/workspace-ui';

export default function Accept({
    invitation,
    acceptUrl,
}: {
    invitation: {
        name: string;
        role: string;
        expires_at: string;
        active: boolean;
    };
    acceptUrl: string;
}) {
    const { data, setData, post, processing, errors } = useForm({ token: '' });
    useEffect(() => {
        const token =
            new URLSearchParams(window.location.hash.slice(1)).get('token') ??
            '';
        if (token) {
            setData('token', token);
        }
        window.history.replaceState(
            window.history.state,
            '',
            window.location.pathname,
        );
    }, [setData]);
    return (
        <div className="mx-auto max-w-xl space-y-5 p-6">
            <Head title="Join a business" />
            <h1 className="text-2xl font-semibold">
                Join / যোগ দিন: {invitation.name}
            </h1>
            <p>Role / কাজের অনুমতি: {invitation.role}</p>
            <p>Expires / মেয়াদ: {invitation.expires_at} UTC</p>
            {invitation.active ? (
                <form
                    className="space-y-4"
                    onSubmit={(e) => {
                        e.preventDefault();
                        post(acceptUrl);
                    }}
                >
                    <p>
                        এই invitation গ্রহণ করলে আপনি এই কোম্পানির কর্মী হিসেবে যোগ
                        হবেন। / Accept to join this business.
                    </p>
                    {!data.token && (
                        <p role="alert">
                            মূল invitation link-টি আবার খুলুন। / Reopen the original
                            invitation link to load its security token.
                        </p>
                    )}
                    <FieldErrors errors={errors} />
                    <Button disabled={processing || !data.token}>
                        Accept invitation / গ্রহণ করুন
                    </Button>
                </form>
            ) : (
                <p role="alert">
                    এই invitation আর সক্রিয় নেই বা মেয়াদ শেষ। মালিককে নতুন link দিতে
                    বলুন। / This invitation is no longer active. Ask the owner
                    for a new link.
                </p>
            )}
            <Link className="underline" href="/dashboard">
                Back to your businesses / ফিরে যান
            </Link>
        </div>
    );
}
Accept.layout = { breadcrumbs: [{ title: 'Workspaces', href: '/dashboard' }] };
