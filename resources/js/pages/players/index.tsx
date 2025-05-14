import type { BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Players',
        href: '/manage/players',
    },
];

export default function Players() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Players" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            </div>
        </AppLayout>
    );
}
