import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { DataTable } from '@/components/ui/data-table';
import { DataTableControls } from '@/components/ui/data-table-controls';
import DataTablePagination from '@/components/ui/data-table-pagination';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import ClubLayout from '@/layouts/club/layout';
import { Club } from '@/pages/clubs/index';
import { getClubPlayerColumns } from '@/pages/clubs/players/partials/columns';
import { Player } from '@/pages/players/index';
import type { BreadcrumbItem, PageFilters, PaginationLink } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { format } from 'date-fns';
import { CalendarIcon, Plus } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

export type BeltRankModel = {
    id: string;
    code: string;
    name: string;
    color: string | null;
    sort_order: number;
};

export type ClubMembership = {
    id: string;
    club_id: string;
    player_id: string;
    player: {
        id: string;
        name: string;
        profile_photo_url: string | null;
    };
    started_at: string;
    ended_at: string | null;
    belt_rank_id: string | null;
    belt_rank: BeltRankModel | null;
    grade_certificate: string | null;
    created_at: string;
    updated_at: string;
};

type ClubPlayersProps = {
    club: Club;
    memberships: {
        data: ClubMembership[];
        links: PaginationLink[];
    };
    availablePlayers: Pick<Player, 'id' | 'name'>[];
    beltRanks: BeltRankModel[];
    filters: PageFilters;
};

export default function ClubPlayers({ club, memberships, availablePlayers, beltRanks, filters }: ClubPlayersProps) {
    const { data: membershipData, links } = memberships;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Clubs', href: '/manage/clubs' },
        { title: club.name, href: `/manage/clubs/${club.id}` },
        { title: 'Players', href: `/manage/clubs/${club.id}/players` },
    ];

    const hasMounted = useRef(false);
    const sortOptions: Record<string, string> = { started_at: 'Joined Date', ended_at: 'Ended Date' };
    const statusOptions: Record<string, string> = { active: 'Active', ended: 'Ended', all: 'All' };

    const [status, setStatus] = useState<'active' | 'ended' | 'all'>((filters.status ?? 'active') as 'active' | 'ended' | 'all');
    const [search, setSearch] = useState(filters.keyword ?? '');
    const [debouncedSearch, setDebouncedSearch] = useState(search);
    const [sortBy, setSortBy] = useState(filters.sort_by ?? 'started_at');
    const [sortOrder, setSortOrder] = useState(filters.sort_order ?? 'desc');
    const [pageNumber, setPageNumber] = useState(filters.page ? Number(filters.page) : 1);
    const [perPage, setPerPage] = useState(filters.per_page ? Number(filters.per_page) : 15);

    useEffect(() => {
        const handler = setTimeout(() => setDebouncedSearch(search), 500);
        return () => clearTimeout(handler);
    }, [search]);

    useEffect(() => {
        if (!hasMounted.current) {
            hasMounted.current = true;
            return;
        }
        router.get(
            `/manage/clubs/${club.id}/players`,
            { keyword: debouncedSearch, status, sort_by: sortBy, sort_order: sortOrder, page: pageNumber, per_page: perPage },
            { only: ['memberships', 'filters'], preserveState: true, preserveScroll: true, replace: true },
        );
    }, [debouncedSearch, status, sortBy, sortOrder, pageNumber, perPage]);

    const handleSearchChange = (val: string) => { setSearch(val); setPageNumber(1); };
    const handleSortByChange = (val: string) => { setSortBy(val); setPageNumber(1); };
    const handleStatusChange = (val: string) => {
        // Empty = clicked the already-selected item; clicking a specific status clears to 'all',
        // clicking 'all' when already 'all' returns to 'active' (the default).
        const next = (val || (status !== 'all' ? 'all' : 'active')) as 'active' | 'ended' | 'all';
        setStatus(next);
        setPageNumber(1);
    };

    const [assignOpen, setAssignOpen] = useState(false);
    const [startDateOpen, setStartDateOpen] = useState(false);
    const today = new Date();
    const [startDate, setStartDate] = useState<Date>(today);

    const assignForm = useForm({
        player_id: '',
        started_at: format(today, 'yyyy-MM-dd'),
        belt_rank_id: '',
        grade_certificate: '',
    });

    const closeAssignDialog = () => {
        setAssignOpen(false);
        const now = new Date();
        setStartDate(now);
        assignForm.reset();
        assignForm.setData('started_at', format(now, 'yyyy-MM-dd'));
    };

    const handleAssign = (e: React.FormEvent) => {
        e.preventDefault();
        assignForm.post(`/manage/clubs/${club.id}/players`, {
            onSuccess: () => closeAssignDialog(),
        });
    };

    const [membershipToEnd, setMembershipToEnd] = useState<ClubMembership | null>(null);
    const [endDate, setEndDate] = useState<Date | undefined>();
    const [endDateOpen, setEndDateOpen] = useState(false);

    const endForm = useForm({ ended_at: '' });

    const closeEndMembershipDialog = () => {
        setMembershipToEnd(null);
        setEndDate(undefined);
        endForm.reset();
    };

    const handleEndMembership = (e: React.FormEvent) => {
        e.preventDefault();
        if (!membershipToEnd) {
            return;
        }
        endForm.put(`/manage/clubs/${club.id}/players/${membershipToEnd.id}`, {
            onSuccess: () => closeEndMembershipDialog(),
        });
    };

    const columns = getClubPlayerColumns({
        onEndMembership: (membership) => {
            setMembershipToEnd(membership);
            setEndDate(undefined);
            endForm.reset();
        },
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${club.name} — Players`} />

            <ClubLayout club={club} activeKey="players">
                <div>
                    <div className="flex items-center justify-between pb-4">
                        <HeadingSmall title="Players" description={`Manage player memberships for ${club.name}.`} />
                        <Button variant="default" onClick={() => setAssignOpen(true)}>
                            <Plus className="mr-2 h-4 w-4" />
                            Assign Player
                        </Button>
                    </div>

                    <DataTableControls
                        search={search}
                        onSearchChange={handleSearchChange}
                        sortBy={sortBy}
                        sortOrder={sortOrder}
                        onSortByChange={handleSortByChange}
                        onSortOrderChange={setSortOrder}
                        sortOptions={sortOptions}
                        statusOptions={statusOptions}
                        statusValue={status}
                        onStatusChange={handleStatusChange}
                    />

                    <DataTable columns={columns} data={membershipData} />
                    <DataTablePagination
                        links={links as PaginationLink[]}
                        perPage={perPage}
                        onPageChange={setPageNumber}
                        onPerPageCountUpdated={(val) => { setPerPage(val); setPageNumber(1); }}
                    />
                </div>
            </ClubLayout>

            <Dialog open={assignOpen} onOpenChange={(open) => !open && closeAssignDialog()}>
                <DialogContent onInteractOutside={(e) => e.preventDefault()}>
                    <DialogHeader>
                        <DialogTitle>Assign Player</DialogTitle>
                        <DialogDescription>Add a player to {club.name}.</DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleAssign} className="space-y-4">
                        <div>
                            <Label htmlFor="player_id">Player</Label>
                            <Select
                                value={assignForm.data.player_id}
                                onValueChange={(value) => assignForm.setData('player_id', value)}
                            >
                                <SelectTrigger className="mt-1 w-full">
                                    <SelectValue placeholder="Select a player" />
                                </SelectTrigger>
                                <SelectContent>
                                    {availablePlayers.length === 0 ? (
                                        <div className="px-3 py-6 text-center text-sm text-muted-foreground">
                                            No players available to assign. All players are already members of a club.
                                        </div>
                                    ) : (
                                        availablePlayers.map((player) => (
                                            <SelectItem key={player.id} value={player.id}>
                                                {player.name}
                                            </SelectItem>
                                        ))
                                    )}
                                </SelectContent>
                            </Select>
                            <InputError message={assignForm.errors.player_id} />
                        </div>

                        <div>
                            <Label>Start Date</Label>
                            <Popover open={startDateOpen} onOpenChange={setStartDateOpen} modal={true}>
                                <PopoverTrigger asChild>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="mt-1 w-full justify-start text-left font-normal"
                                    >
                                        <CalendarIcon className="mr-2 h-4 w-4" />
                                        {format(startDate, 'PPP')}
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent className="w-auto p-0">
                                    <Calendar
                                        mode="single"
                                        selected={startDate}
                                        onSelect={(date) => {
                                            if (!date) return;
                                            setStartDate(date);
                                            assignForm.setData('started_at', format(date, 'yyyy-MM-dd'));
                                            setStartDateOpen(false);
                                        }}
                                        disabled={(date) => date > new Date()}
                                        initialFocus
                                    />
                                </PopoverContent>
                            </Popover>
                            <InputError message={assignForm.errors.started_at} />
                        </div>

                        <div>
                            <Label htmlFor="belt_rank_id">
                                Belt Rank <span className="text-xs font-light">(Optional)</span>
                            </Label>
                            <Select
                                value={assignForm.data.belt_rank_id}
                                onValueChange={(value) => assignForm.setData('belt_rank_id', value)}
                            >
                                <SelectTrigger className="mt-1 w-full">
                                    <SelectValue placeholder="Select belt rank" />
                                </SelectTrigger>
                                <SelectContent>
                                    {beltRanks.map((rank) => (
                                        <SelectItem key={rank.id} value={rank.id}>
                                            {rank.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={assignForm.errors.belt_rank_id} />
                        </div>

                        <div>
                            <Label htmlFor="grade_certificate">
                                Grade Certificate <span className="text-xs font-light">(Optional)</span>
                            </Label>
                            <Input
                                id="grade_certificate"
                                value={assignForm.data.grade_certificate}
                                onChange={(e) => assignForm.setData('grade_certificate', e.target.value)}
                                placeholder="e.g. 1st dan"
                                className="mt-1 block w-full"
                            />
                            <InputError message={assignForm.errors.grade_certificate} />
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={closeAssignDialog}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={assignForm.processing}>
                                {assignForm.processing ? 'Assigning...' : 'Assign'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={!!membershipToEnd} onOpenChange={(open) => !open && closeEndMembershipDialog()}>
                <DialogContent onInteractOutside={(e) => e.preventDefault()}>
                    <DialogHeader>
                        <DialogTitle>End Membership</DialogTitle>
                        <DialogDescription>
                            Set the end date for {membershipToEnd?.player.name}&apos;s membership at {club.name}.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleEndMembership} className="space-y-4">
                        <div>
                            <Label>End Date</Label>
                            <Popover open={endDateOpen} onOpenChange={setEndDateOpen} modal={true}>
                                <PopoverTrigger asChild>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="mt-1 w-full justify-start text-left font-normal"
                                    >
                                        <CalendarIcon className="mr-2 h-4 w-4" />
                                        {endDate ? format(endDate, 'PPP') : <span className="text-muted-foreground">Pick a date</span>}
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent className="w-auto p-0">
                                    <Calendar
                                        mode="single"
                                        selected={endDate}
                                        onSelect={(date) => {
                                            if (!date) return;
                                            setEndDate(date);
                                            endForm.setData('ended_at', format(date, 'yyyy-MM-dd'));
                                            setEndDateOpen(false);
                                        }}
                                        disabled={(date) => {
                                            if (!membershipToEnd) return false;
                                            const startOfStartedAt = new Date(membershipToEnd.started_at);
                                            startOfStartedAt.setHours(0, 0, 0, 0);
                                            const endOfToday = new Date();
                                            endOfToday.setHours(23, 59, 59, 999);
                                            return date < startOfStartedAt || date > endOfToday;
                                        }}
                                        initialFocus
                                    />
                                </PopoverContent>
                            </Popover>
                            <InputError message={endForm.errors.ended_at} />
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={closeEndMembershipDialog}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={endForm.processing}>
                                {endForm.processing ? 'Saving...' : 'End Membership'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
