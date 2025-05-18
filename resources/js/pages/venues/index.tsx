import Heading from '@/components/heading';
import SortAscendingIcon from '@/components/svg/sort-ascending';
import SortDescendingIcon from '@/components/svg/sort-descending';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import DataTablePagination, { PaginationLink } from '@/components/ui/data-table-pagination';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
import React, { useState } from 'react';
import { columns } from './columns';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Venue List',
        href: '/manage/venues',
    },
];

export default function Venues() {
    const { venues, filters } = usePage<SharedData>().props;
    const { data, links } = venues;

    const [sortOrder, setSortOrder] = useState(null);

    const handleSortOrderUpdated = (order) => {
        setSortOrder(order);
        // Add your logic to handle the sort change
    };

    function handlePageChange(url: string) {
        router.get(url, filters, { only: ['venues'], replace: true, preserveState: true, preserveScroll: true });
    }

    const { setData, get } = useForm({
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
                <div className="flex items-center">
                    <Heading title="Venues" />
                    <Button variant="default" className="ml-auto">
                        New Venue
                    </Button>
                </div>

                <div className="mx-auto">
                    <div className="flex items-center pb-4">
                        <Input placeholder="Search..." value={data.keyword} onChange={handleSearchChange} className="mr-2 block max-w-sm" />

                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="outline" className="mr-2 ml-auto" title="Sort by">
                                    Sort By
                                    <ChevronDown />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem>Name</DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem>Updated date</DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <div className="flex items-center">
                            <Button variant={'outline'} size={'icon'} className="rounded-r-none" title="Sort ascending">
                                <SortAscendingIcon />
                            </Button>
                            <Button variant={'outline'} size={'icon'} className="rounded-l-none" title="Sort descending">
                                <SortDescendingIcon />
                            </Button>
                        </div>
                    </div>

                    <DataTable columns={columns} data={data} />

                    <DataTablePagination
                        links={links as PaginationLink[]}
                        perPage={venues.per_page || 50}
                        onPageChange={(page: number) => {
                            const baseUrl = route('venues.index');
                            const newParams = new URLSearchParams({ ...filters, page: String(page) });
                            router.get(
                                `${baseUrl}?${newParams.toString()}`,
                                {},
                                {
                                    only: ['venues'],
                                    preserveState: true,
                                    preserveScroll: true,
                                },
                            );
                        }}
                        onPerPageCountUpdated={(perPage: number) => {
                            const newParams = new URLSearchParams({ ...filters, perPage: String(perPage) });
                            router.get(
                                route('venues.index') + '?' + newParams.toString(),
                                {},
                                {
                                    only: ['venues'],
                                    preserveState: true,
                                    preserveScroll: true,
                                },
                            );
                        }}
                    />
                </div>
            </div>
        </AppLayout>
    );
}
