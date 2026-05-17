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

// ── Enums ──────────────────────────────────────────────────────────────────

export enum TournamentStatus {
    Scheduled = 0,
    InProgress = 1,
    Completed = 2,
    Cancelled = 3,
}

export enum TournamentLevel {
    Local = 1,
    Regional = 2,
    National = 3,
    International = 4,
}

export enum CategoryStatus {
    Pending = 0,
    Seeding = 1,
    Active = 2,
    Completed = 3,
}

export enum RegistrationStatus {
    Registered = 0,
    Confirmed = 1,
    Withdrawn = 2,
}

export enum Gender {
    Male = 0,
    Female = 1,
}

export enum AgeCategory {
    Preparation = 0,
    SuperMini = 1,
    Mini = 2,
    Pupils = 3,
    Juveniles = 4,
    Cadets = 5,
    Juniors = 6,
    Under23 = 7,
    Seniors = 8,
    Veterans = 9,
}

export enum WeightCategory {
    Female48 = 0,
    Female52 = 1,
    Female57 = 2,
    Male60 = 3,
    Female63 = 4,
    Male66 = 5,
    Female70 = 6,
    Male73 = 7,
    Female78 = 8,
    Male81 = 9,
    Male90 = 10,
    Male100 = 11,
    Male100Plus = 12,
    Female78Plus = 13,
}

export enum BracketType {
    SingleElimination = 0,
    DoubleElimination = 1,
    PoolFinal = 2,
    Repechage = 3,
}

export enum BracketStatus {
    Pending = 0,
    Active = 1,
    Completed = 2,
}

export enum BracketSection {
    Main = 0,
    Repechage = 1,
    Bronze = 2,
    Final = 3,
}

export enum MatchStatus {
    Pending = 0,
    Active = 1,
    Completed = 2,
}

export enum CompetitorPosition {
    White = 0,
    Blue = 1,
}

export enum CompetitorResult {
    Pending = 0,
    Win = 1,
    Loss = 2,
    WalkoverWin = 3,
    Disqualified = 4,
}

// ── Types ──────────────────────────────────────────────────────────────────

export type Tournament = {
    id: string;
    name: string;
    description: string | null;
    logo_url: string | null;
    level: TournamentLevel;
    start_at: string;
    end_at: string | null;
    status: TournamentStatus;
    venue_id: string;
    created_at: string;
    updated_at: string;
};

export type TournamentCategory = {
    id: string;
    tournament_id: string;
    name: string | null;
    gender: Gender;
    age_category: AgeCategory;
    min_age: number;
    max_age: number;
    weight_category: WeightCategory;
    min_weight: number;
    max_weight: number;
    status: CategoryStatus;
    registrations_count?: number;
    created_at: string;
    updated_at: string;
};

export type TournamentRegistration = {
    id: string;
    tournament_category_id: string;
    club_id: string;
    player_id: string;
    player_age: number;
    player_weight: number;
    status: RegistrationStatus;
    player?: {
        id: string;
        name: string;
        profile_photo_url: string | null;
    };
    club?: {
        id: string;
        name: string;
    };
};

export type MatchCompetitor = {
    registration_id: string;
    player_id: string;
    name: string;
    seed: number | null;
    score: number | null;
    penalties: number | null;
    result: CompetitorResult;
    profile_photo_url: string | null;
};

export type BracketMatch = {
    id: string;
    match_number: number;
    white: MatchCompetitor | null;
    blue: MatchCompetitor | null;
    winner_id: string | null;
    status: MatchStatus;
    scheduled_at: string | null;
    notes: string | null;
};

export type BracketRound = {
    round_number: number;
    round_name: string;
    matches: BracketMatch[];
};

export type BracketStructure = {
    id: string;
    type: BracketType;
    status: BracketStatus;
    sections: {
        Main?: BracketRound[];
        Repechage?: BracketRound[];
        Bronze?: BracketRound[];
        Final?: BracketRound[];
    };
};

// ── Page ───────────────────────────────────────────────────────────────────

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
