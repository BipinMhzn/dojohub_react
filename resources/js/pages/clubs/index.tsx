import type { BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Clubs',
        href: '/manage/clubs',
    },
];

export default function Clubs() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Clubs" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            </div>
        </AppLayout>
    );
}
