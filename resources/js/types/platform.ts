export type Workspace = {
    id: string;
    name: string;
    industry: string;
    locale: 'en' | 'bn';
    currency: string;
    timezone: string;
};
export type Lead = {
    id: string;
    name: string;
    email: string | null;
    phone: string | null;
    source: string;
    status: string;
};
export type Pagination<T> = {
    data: T[];
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
};
