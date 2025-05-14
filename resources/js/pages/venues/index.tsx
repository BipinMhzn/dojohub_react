import type { BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Venues',
        href: '/manage/venues',
    },
];

export default function Venues() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Venues" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            </div>
        </AppLayout>
    );
}
