import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { DataTableControls } from '@/components/ui/data-table-controls';
import DataTablePagination from '@/components/ui/data-table-pagination';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import { getCoachColumns } from '@/pages/coaches/partials/columns';
import type { BreadcrumbItem, PageFilters, PaginationLink } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Coaches',
        href: '/manage/coaches',
    },
];

export type ClubOption = {
    id: string;
    name: string;
};

export type CoachMembership = {
    id: string;
    club_id: string;
    started_at: string;
    ended_at: string | null;
    club?: ClubOption | null;
};

export type Coach = {
    id: string;
    user_id: string | null;
    full_name: string;
    email: string | null;
    phone_number: string | null;
    active_memberships?: CoachMembership[];
    created_at: string;
    updated_at: string;
};

type CoachesProps = {
    coaches: {
        data: Coach[];
        links: PaginationLink[];
    };
    filters: PageFilters;
};

export default function Coaches({ coaches, filters }: CoachesProps) {
    const hasMounted = useRef(false);

    const { data, links } = coaches;
    const sortOptions: Record<string, string> = {
        full_name: 'Name',
        updated_at: 'Last Updated',
    };

    const [search, setSearch] = useState(filters.keyword ?? '');
    const [sortBy, setSortBy] = useState(filters.sort_by ?? 'full_name');
    const [sortOrder, setSortOrder] = useState(filters.sort_order ?? 'asc');
    const [pageNumber, setPageNumber] = useState(filters.page ? Number(filters.page) : 1);
    const [perPage, setPerPage] = useState(filters.per_page ? Number(filters.per_page) : 50);
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
            '/manage/coaches',
            {
                keyword: debouncedSearch,
                sort_by: sortBy,
                sort_order: sortOrder,
                page: pageNumber,
                per_page: perPage,
            },
            {
                only: ['coaches'],
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

    const [coachToDelete, setCoachToDelete] = useState<Coach | null>(null);
    const columns = getCoachColumns({
        onDelete: (coach) => setCoachToDelete(coach),
    });

    const handleConfirmDelete = () => {
        if (coachToDelete) {
            router.delete(`/manage/coaches/${coachToDelete.id}`);
            setCoachToDelete(null);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Coaches" />

            <div className="px-4 py-6">
                <div className="flex items-center">
                    <Heading title="Coaches" description="Manage coaches for clubs and tournaments" />
                    <Button variant="default" className="ml-auto" asChild>
                        <Link href="/manage/coaches/create">New Coach</Link>
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

            <Dialog open={!!coachToDelete} onOpenChange={(open) => !open && setCoachToDelete(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Coach</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete {coachToDelete?.full_name}? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setCoachToDelete(null)}>
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
