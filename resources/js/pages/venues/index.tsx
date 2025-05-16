import Heading from '@/components/heading';
import { DataTable } from '@/components/ui/data-table';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { columns } from './columns';
import React, { useState } from 'react';
import { Input } from '@/components/ui/input';

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

    const [sortOrder, setSortOrder] = useState(null);

    const handleSortOrderUpdated = (order) => {
        setSortOrder(order);
        // Add your logic to handle the sort change
    };

    function handlePageChange(url: string) {
        router.get(url, filters, { only: ['venues'], replace: true, preserveState: true, preserveScroll: true });
    }
    const {setData, get } = useForm({
        keyword: filters.keyword || '',
    });

    function handleSearchChange(e: React.ChangeEvent<HTMLInputElement>) {
        setData('keyword', e.target.value);
        get(route('venues.index'), {
            preserveState: true,
            preserveScroll: true,
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Venues" />

            <div className="px-4 py-6">
                <Heading title="Venues" description="Manage your venues" />

                <div className="container mx-auto">
                    <div className="flex items-center py-4">
                        <Input
                            placeholder="Search..."
                            value={data.keyword}
                            onChange={handleSearchChange}
                            className="max-w-sm"
                        />

                        <div className="flex items-center bg-white">
                            <button
                                id="asc-order"
                                className={`px-2 py-1.5 text-sm font-medium rounded-l-md border border-primary-200 hover:bg-gray-100 focus:ring-1 focus:ring-primary focus:border-primary focus:outline-none ${
                                    sortOrder === 'asc' ? 'bg-primary-50 hover:bg-primary-50' : ''
                                }`}
                                onClick={() => handleSortOrderUpdated('asc')}
                                title="Sort ascending"
                            >
                                <img className="h-6 w-6" src="/svg/sort-ascending.svg" alt="sort-ascending" />
                            </button>
                            <button
                                id="desc-order"
                                className={`px-2 py-1.5 text-sm font-medium rounded-r-md border border-primary-200 hover:bg-gray-100 focus:ring-1 focus:ring-primary focus:border-primary focus:outline-none ${
                                    sortOrder === 'desc' ? 'bg-primary-50 hover:bg-primary-50' : ''
                                }`}
                                onClick={() => handleSortOrderUpdated('desc')}
                                title="Sort descending"
                            >
                                <img className="h-6 w-6" src="/svg/sort-descending.svg" alt="sort-descending" />
                            </button>
                        </div>
                    </div>

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
