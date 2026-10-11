import { Head, Link } from '@inertiajs/react';

export default function Welcome() {
    return (
        <main className="mx-auto max-w-4xl space-y-8 p-8">
            <Head title="NovaFlow — ERP + CRM" />
            <div className="text-sm font-medium">
                NovaFlow · Foundation preview
            </div>
            <h1 className="text-4xl font-bold">
                One platform. Your business, your workflow.
            </h1>
            <p className="text-xl">
                ব্যবসাভেদে কাস্টমাইজযোগ্য ERP + CRM। এখন workspace, lead management
                এবং ব্যবসার পরিভাষা পরিবর্তনের প্রাথমিক সংস্করণ ব্যবহার করুন।
            </p>
            <div className="flex gap-6">
                <Link
                    className="rounded bg-primary px-5 py-3 text-primary-foreground"
                    href="/register"
                >
                    Create account
                </Link>
                <Link className="rounded border px-5 py-3" href="/login">
                    Sign in
                </Link>
                <Link className="rounded border px-5 py-3" href="/dashboard">
                    Workspaces
                </Link>
            </div>
            <section className="rounded-xl border p-6">
                <h2 className="text-xl font-semibold">What is available?</h2>
                <p>
                    Separate business workspaces, membership permissions, leads,
                    search, status updates, versioned terminology and backend
                    audit records.
                </p>
                <p className="mt-4 text-muted-foreground">
                    Sales, inventory, accounting, billing and industry packs are
                    roadmap items. This preview is not ready for live financial
                    records.
                </p>
            </section>
        </main>
    );
}
