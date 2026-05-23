import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { DataTableControls } from '@/components/ui/data-table-controls';
import DataTablePagination from '@/components/ui/data-table-pagination';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import { getClubColumns } from '@/pages/clubs/partials/columns';
import type { BreadcrumbItem, PageFilters, PaginationLink } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Clubs',
        href: '/manage/clubs',
    },
];

export type ClubAddress = {
    id: string;
    address_line_1: string;
    address_line_2: string | null;
    city: string | null;
    state: string | null;
    postal_code: string | null;
    country_code: string;
    country?: { code: string; name: string };
};

export type Country = {
    code: string;
    name: string;
};

export type Club = {
    id: string;
    name: string;
    abbreviation: string;
    logo_url: string | null;
    address: ClubAddress | null;
    phone_number: string | null;
    created_at: string;
    updated_at: string;
};

type ClubsProps = {
    clubs: {
        data: Club[];
        links: PaginationLink[];
    };
    filters: PageFilters;
};

export default function Clubs({ clubs, filters }: ClubsProps) {
    const hasMounted = useRef(false);

    const { data, links } = clubs;
    const sortOptions: Record<string, string> = {
        name: 'Name',
        updated_at: 'Last Updated',
    };

    const [search, setSearch] = useState(filters.keyword ?? '');
    const [sortBy, setSortBy] = useState(filters.sort_by ?? 'name');
    const [sortOrder, setSortOrder] = useState(filters.sort_order ?? 'asc');
    const [pageNumber, setPageNumber] = useState(filters.page ? Number(filters.page) : 1);
    const [perPage, setPerPage] = useState(filters.per_page ? Number(filters.per_page) : 10);
    const [debouncedSearch, setDebouncedSearch] = useState(search);

    useEffect(() => {
        const handler = setTimeout(() => {
            setDebouncedSearch(search);
        }, 500);

        return () => clearTimeout(handler);
    }, [search]);

    useEffect(() => {
        if (!hasMounted.current) {
            hasMounted.current = true;
            return;
        }
        router.get(
            '/manage/clubs',
            {
                keyword: debouncedSearch,
                sort_by: sortBy,
                sort_order: sortOrder,
                page: pageNumber,
                per_page: perPage,
            },
            {
                only: ['clubs'],
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }, [debouncedSearch, sortBy, sortOrder, pageNumber, perPage]);

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
    };

    const [clubToDelete, setClubToDelete] = useState<Club | null>(null);
    const columns = getClubColumns({
        onDelete: (club) => setClubToDelete(club),
    });

    const handleConfirmDelete = () => {
        if (clubToDelete) {
            router.delete(`/manage/clubs/${clubToDelete.id}`);
            setClubToDelete(null);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Clubs" />

            <div className="px-4 py-6">
                <div className="flex items-center">
                    <Heading title="Clubs" description="Manage clubs for tournament matches" />
                    <Button variant="default" className="ml-auto" asChild>
                        <Link href="/manage/clubs/create">New Club</Link>
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

                    <DataTable columns={columns} data={data} onRowClick={(club) => router.visit(`/manage/clubs/${club.id}`)} />

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

            <Dialog open={!!clubToDelete} onOpenChange={(open) => !open && setClubToDelete(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Club</DialogTitle>
                        <DialogDescription>Are you sure you want to delete {clubToDelete?.name}? This action cannot be undone.</DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setClubToDelete(null)}>
                            Cancel
                        </Button>
                        <Button variant="destructive" onClick={handleConfirmDelete}>
                            Delete
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
