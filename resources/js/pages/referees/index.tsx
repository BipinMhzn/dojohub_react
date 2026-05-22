import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { DataTableControls } from '@/components/ui/data-table-controls';
import DataTablePagination from '@/components/ui/data-table-pagination';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import { getRefereeColumns } from '@/pages/referees/partials/columns';
import type { BreadcrumbItem, PageFilters, PaginationLink } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Referees',
        href: '/manage/referees',
    },
];

export enum RefereeCertificationLevel {
    Club = 0,
    National = 1,
    Continental = 2,
    International = 3,
}

export const certificationLevelLabels: Record<RefereeCertificationLevel, string> = {
    [RefereeCertificationLevel.Club]: 'Club',
    [RefereeCertificationLevel.National]: 'National',
    [RefereeCertificationLevel.Continental]: 'Continental',
    [RefereeCertificationLevel.International]: 'International',
};

export type CountryOption = {
    code: string;
    name: string;
};

export type CertificationLevelOption = {
    name: string;
    value: number;
};

export type Referee = {
    id: string;
    user_id: string | null;
    full_name: string;
    nationality_code: string | null;
    certification_level: RefereeCertificationLevel | null;
    country?: CountryOption;
    created_at: string;
    updated_at: string;
};

type RefereesProps = {
    referees: {
        data: Referee[];
        links: PaginationLink[];
    };
    filters: PageFilters;
};

export default function Referees({ referees, filters }: RefereesProps) {
    const hasMounted = useRef(false);

    const { data, links } = referees;
    const sortOptions: Record<string, string> = {
        full_name: 'Name',
        updated_at: 'Last Updated',
    };

    const [search, setSearch] = useState(filters.keyword ?? '');
    const [sortBy, setSortBy] = useState(filters.sort_by ?? 'full_name');
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
            '/manage/referees',
            {
                keyword: debouncedSearch,
                sort_by: sortBy,
                sort_order: sortOrder,
                page: pageNumber,
                per_page: perPage,
            },
            {
                only: ['referees'],
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

    const [refereeToDelete, setRefereeToDelete] = useState<Referee | null>(null);
    const columns = getRefereeColumns({
        onDelete: (referee) => setRefereeToDelete(referee),
    });

    const handleConfirmDelete = () => {
        if (refereeToDelete) {
            router.delete(`/manage/referees/${refereeToDelete.id}`);
            setRefereeToDelete(null);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Referees" />

            <div className="px-4 py-6">
                <div className="flex items-center">
                    <Heading title="Referees" description="Manage referees for tournament matches" />
                    <Button variant="default" className="ml-auto" asChild>
                        <Link href="/manage/referees/create">New Referee</Link>
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

            <Dialog open={!!refereeToDelete} onOpenChange={(open) => !open && setRefereeToDelete(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Referee</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete {refereeToDelete?.full_name}? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setRefereeToDelete(null)}>
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
