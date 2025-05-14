import type { BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tournaments',
        href: '/manage/tournaments',
    },
];

export default function Tournaments() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tournaments" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            </div>
        </AppLayout>
    );
}
