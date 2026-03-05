import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { DataTableControls } from '@/components/ui/data-table-controls';
import DataTablePagination from '@/components/ui/data-table-pagination';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import { getTournamentColumns } from '@/pages/tournaments/partials/columns';
import type { BreadcrumbItem, PaginationLink } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tournaments',
        href: '/manage/tournaments',
    },
];

export enum TournamentStatus {
    SCHEDULED = 0,
    ONGOING = 1,
    COMPLETED = 2,
    CANCELLED = 3,
}

export enum TournamentLevel {
    LOCAL = 0,
    REGIONAL = 1,
    NATIONAL = 2,
}

export enum TournamentGender {
    MALE = 0,
    FEMALE = 1,
}

export enum TournamentType {
    SINGLE_ELIMINATION = 0,
    DOUBLE_ELIMINATION = 1,
    ROUND_ROBIN = 2,
}

export enum MatchStatus {
    PENDING = 0,
    ONGOING = 1,
    COMPLETED = 2,
    CANCELLED = 3,
}

export type Tournament = {
    id: string;
    name: string;
    description: string | null;
    logo_url: string | null;
    level: TournamentLevel;
    type: TournamentType;
    start_at: Date;
    end_at: string | null;
    weight_category: string;
    min_weight: number;
    max_weight: number;
    age_category: string;
    min_age: number;
    max_age: number;
    gender: TournamentGender;
    status: TournamentStatus;
    venue_id: string;
    created_at: string;
    updated_at: string;
};

export type MatchPlayer = {
    id: string;
    name: string;
    seed: number | null;
    score: number | null;
    profile_photo: string | null;
};

export type Match = {
    id: string;
    match_order: number;
    player1: MatchPlayer | null;
    player2: MatchPlayer | null;
    winner_id: string | null;
    status: number;
    scheduled_at: string | null;
    has_bye: boolean;
};

export type BracketRound = {
    round_number: number;
    round_name: string;
    matches: Match[];
};

export type BracketStructure = {
    type: number;
    brackets: {
        main: BracketRound[];
        losers?: BracketRound[];
    };
};

type TournamentsProps = {
    tournaments: {
        data: Tournament[];
        links: PaginationLink[];
    };
    filters: {
        keyword?: string;
        sort_by?: string;
        sort_order?: string;
        page?: number;
        per_page?: number;
    };
};

export default function Tournaments({ tournaments, filters }: TournamentsProps) {
    const hasMounted = useRef(false);

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
            '/manage/tournaments',
            {
                keyword: debouncedSearch,
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

    const [tournamentToDelete, setTournamentToDelete] = useState<Tournament | null>(null);
    const columns = getTournamentColumns({
        onDelete: (tournament) => setTournamentToDelete(tournament),
    });

    const handleConfirmDelete = () => {
        if (tournamentToDelete) {
            router.delete(`/manage/tournaments/${tournamentToDelete.id}`);
            setTournamentToDelete(null);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tournaments" />
            <div className="px-4 py-6">
                <div className="flex items-center">
                    <Heading title="Tournaments" description="Manage your tournaments" />
                    <Button variant="default" className="ml-auto" asChild>
                        <Link href="/manage/tournaments/create">New Tournament</Link>
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

            <Dialog open={!!tournamentToDelete} onOpenChange={(open) => !open && setTournamentToDelete(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Tournament</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete {tournamentToDelete?.name}? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setTournamentToDelete(null)}>
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
