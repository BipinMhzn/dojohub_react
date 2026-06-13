import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { DataTableControls } from '@/components/ui/data-table-controls';
import DataTablePagination from '@/components/ui/data-table-pagination';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import { getVenueColumns } from '@/pages/venues/partials/columns';
import type { BreadcrumbItem, PaginationLink } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Venues', href: '/manage/venues' }];

export type VenueAddress = {
    id: string;
    address_line_1: string;
    address_line_2: string | null;
    city: string;
    state: string | null;
    postal_code: string | null;
    country_code: string;
    country?: { code: string; name: string };
};

export type VenueMat = {
    id: string;
    venue_id: string;
    mat_number: number;
    display_name: string | null;
};

export type Venue = {
    id: string;
    name: string;
    mats_count: number;
    address: VenueAddress;
    mats?: VenueMat[];
    created_at: string;
    updated_at: string;
};

export type Country = {
    code: string;
    name: string;
};

type VenuesProps = {
    venues: { data: Venue[]; links: PaginationLink[] };
    filters: { keyword?: string; sort_by?: string; sort_order?: string; page?: number; per_page?: number };
};

export default function Venues({ venues, filters }: VenuesProps) {
    const hasMounted = useRef(false);
    const { data, links } = venues;

    const sortOptions: Record<string, string> = { name: 'Name', updated_at: 'Last Updated' };

    const [search, setSearch] = useState(filters.keyword ?? '');
    const [sortBy, setSortBy] = useState(filters.sort_by ?? 'name');
    const [sortOrder, setSortOrder] = useState(filters.sort_order ?? 'asc');
    const [pageNumber, setPageNumber] = useState(filters.page ? Number(filters.page) : 1);
    const [perPage, setPerPage] = useState(filters.per_page ? Number(filters.per_page) : 50);
    const [debouncedSearch, setDebouncedSearch] = useState(search);

    useEffect(() => {
        const handler = setTimeout(() => setDebouncedSearch(search), 500);
        return () => clearTimeout(handler);
    }, [search]);

    useEffect(() => {
        if (!hasMounted.current) { hasMounted.current = true; return; }
        router.get('/manage/venues', { keyword: debouncedSearch, sort_by: sortBy, sort_order: sortOrder, page: pageNumber, per_page: perPage }, { only: ['venues'], preserveState: true, preserveScroll: true, replace: true });
    }, [debouncedSearch, sortBy, sortOrder, pageNumber, perPage]);

    const [venueToDelete, setVenueToDelete] = useState<Venue | null>(null);
    const columns = getVenueColumns({ onDelete: (venue) => setVenueToDelete(venue) });

    const handleConfirmDelete = () => {
        if (venueToDelete) { router.delete(`/manage/venues/${venueToDelete.id}`); setVenueToDelete(null); }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Venues" />
            <div className="px-4 py-6">
                <div className="flex items-center">
                    <Heading title="Venues" description="Manage and organize venue locations for tournaments" />
                    <Button variant="default" className="ml-auto" asChild>
                        <Link href="/manage/venues/create">New Venue</Link>
                    </Button>
                </div>
                <div className="mx-auto">
                    <DataTableControls search={search} onSearchChange={(k) => { setSearch(k); setPageNumber(1); }} sortBy={sortBy} sortOrder={sortOrder} onSortByChange={(k) => { setSortBy(k); setPageNumber(1); }} onSortOrderChange={setSortOrder} sortOptions={sortOptions} />
                    <DataTable columns={columns} data={data} onRowClick={(venue) => router.visit(`/manage/venues/${venue.id}`)} />
                    <DataTablePagination links={links as PaginationLink[]} perPage={perPage} onPageChange={(page) => setPageNumber(page)} onPerPageCountUpdated={(pp) => { setPerPage(pp); setPageNumber(1); }} />
                </div>
            </div>
            <Dialog open={!!venueToDelete} onOpenChange={(open) => !open && setVenueToDelete(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Venue</DialogTitle>
                        <DialogDescription>Are you sure you want to delete {venueToDelete?.name}? This action cannot be undone.</DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setVenueToDelete(null)}>Cancel</Button>
                        <Button variant="destructive" onClick={handleConfirmDelete}>Delete</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
