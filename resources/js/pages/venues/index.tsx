import Heading from '@/components/heading';
import { DataTable } from '@/components/ui/data-table';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { columns } from './columns';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Venue List',
        href: '/manage/venues',
    },
];

export default function Venues() {
    const { props } = usePage();
    const { data, links } = props.venues;
    const filters = props.filters;

    function handlePageChange(url: string) {
        router.get(url, filters, { only: ['venues'], replace: true, preserveState: true, preserveScroll: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Venues" />

            <div className="px-4 py-6">
                <Heading title="Venues" description="Manage your venues" />

                <div className="container mx-auto">
                    <DataTable columns={columns} data={data} />

                    <div className="mt-4 flex justify-end space-x-2">
                        {links.map((link: object, i: number) => (
                            <button
                                key={i}
                                disabled={!link.url}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                                className={`rounded border px-3 py-1 text-sm ${
                                    link.active ? 'bg-primary text-white' : 'bg-white text-gray-700 hover:bg-gray-100'
                                }`}
                                onClick={() => handlePageChange(link.url)}
                            />
                        ))}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
