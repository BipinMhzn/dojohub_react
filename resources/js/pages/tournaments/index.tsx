import type { BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { DataTableControls } from '@/components/ui/data-table-controls';
import { DataTable } from '@/components/ui/data-table';
import { columns } from '@/pages/tournaments/columns';
import DataTablePagination, { PaginationLink } from '@/components/ui/data-table-pagination';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tournaments',
        href: '/manage/tournaments',
    },
];

export default function Tournaments({tournaments, filters}) {
    const { data, links } = tournaments;
    const sortOptions: Record<string, string> = {
        name: 'Name',
        updated_at: 'Last Updated',
    };

    const [search, setSearch] = useState(filters.keyword ?? '');
    const [sortBy, setSortBy] = useState(filters.sort_by ?? 'name');
    const [sortOrder, setSortOrder] = useState(filters.sort_order ?? 'asc');
    const [pageNumber, setPageNumber] = useState(filters.page ? Number(filters.page) : 1);
    const [perPage, setPerPage] = useState(filters.per_page ? Number(filters.per_page) : 10);

    useEffect(() => {
        router.get(
            '/manage/tournaments',
            {
                keyword: search,
                sort_by: sortBy,
                sort_order: sortOrder,
                page: pageNumber,
                per_page: perPage,
            },
            {
                only: ['tournaments'],
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }, [search, sortBy, sortOrder, pageNumber, perPage]);

    const handleSearchChange = (key: string) => {
        setSearch(key);
        setPageNumber(1);
    };

    const handleSortByChange = (key: string) => {
        setSortBy(key);
        setPageNumber(1);
    };

    const handlePerPageChange = (perPage: number) => {
        setPerPage(perPage);
        setPageNumber(1);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tournaments" />
            <div className="px-4 py-6">
                <div className="flex items-center">
                    <Heading title="Tournaments" />
                    <Button variant="default" className="ml-auto">
                        New Tournament
                    </Button>
                </div>

                <div className="mx-auto">
                    <DataTableControls
                        search={search}
                        onSearchChange={handleSearchChange}
                        sortBy={sortBy}
                        sortOrder={sortOrder}
                        onSortByChange={handleSortByChange}
                        onSortOrderChange={setSortOrder}
                        sortOptions={sortOptions}
                    />

                    <DataTable columns={columns} data={data} />

                    <DataTablePagination
                        links={links as PaginationLink[]}
                        perPage={perPage}
                        onPageChange={(page: number) => {
                            setPageNumber(page);
                        }}
                        onPerPageCountUpdated={handlePerPageChange}
                    />
                </div>
            </div>
        </AppLayout>
    );
}
