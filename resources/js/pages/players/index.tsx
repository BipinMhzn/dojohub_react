import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { DataTableControls } from '@/components/ui/data-table-controls';
import DataTablePagination from '@/components/ui/data-table-pagination';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import { getPlayerColumns } from '@/pages/players/partials/columns';
import type { BreadcrumbItem, PageFilters, PaginationLink } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Players',
        href: '/manage/players',
    },
];

export enum PlayerGender {
    Male = 0,
    Female = 1,
}

export type BeltRankOption = {
    id: string;
    code: string;
    name: string;
    color: string | null;
};

export type CountryOption = {
    code: string;
    name: string;
};

export type Player = {
    id: string;
    name: string;
    gender: PlayerGender;
    dob: string;
    weight: number;
    nationality_code: string | null;
    current_belt_rank_id: string | null;
    country?: CountryOption;
    current_belt_rank?: BeltRankOption;
    email: string | null;
    profile_photo_url: string | null;
    phone_number: string | null;
    created_at: string;
    updated_at: string;
};

type PlayersProps = {
    players: {
        data: Player[];
        links: PaginationLink[];
    };
    filters: PageFilters;
};

export default function Players({ players, filters }: PlayersProps) {
    const hasMounted = useRef(false);

    const { data, links } = players;
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
            '/manage/players',
            {
                keyword: debouncedSearch,
                sort_by: sortBy,
                sort_order: sortOrder,
                page: pageNumber,
                per_page: perPage,
            },
            {
                only: ['players'],
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

    const [playerToDelete, setPlayerToDelete] = useState<Player | null>(null);
    const columns = getPlayerColumns({
        onDelete: (player) => setPlayerToDelete(player),
    });

    const handleConfirmDelete = () => {
        if (playerToDelete) {
            router.delete(`/manage/players/${playerToDelete.id}`);
            setPlayerToDelete(null);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Players" />

            <div className="px-4 py-6">
                <div className="flex items-center">
                    <Heading title="Players" description="Manage players for tournament matches" />
                    <Button variant="default" className="ml-auto" asChild>
                        <Link href="/manage/players/create">New Player</Link>
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

            <Dialog open={!!playerToDelete} onOpenChange={(open) => !open && setPlayerToDelete(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Player</DialogTitle>
                        <DialogDescription>Are you sure you want to delete {playerToDelete?.name}? This action cannot be undone.</DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setPlayerToDelete(null)}>
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
