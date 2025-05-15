import Heading from '@/components/heading';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { columns, Venue } from './columns';
import { DataTable } from './data-table';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Venues',
        href: '/manage/venues',
    },
];

export default function Venues() {
    const { props } = usePage();
    const venues = props.venues.data as Venue[];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Venues" />

            <div className="px-4 py-6">
                <Heading title="Venues" description="Manage your venues" />

                <div className="container mx-auto">
                    <DataTable columns={columns} data={venues} />
                </div>
            </div>
        </AppLayout>
    );
}
