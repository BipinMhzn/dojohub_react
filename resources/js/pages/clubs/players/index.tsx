import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { DataTable } from '@/components/ui/data-table';
import DataTablePagination from '@/components/ui/data-table-pagination';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import ClubLayout from '@/layouts/club/layout';
import { cn } from '@/lib/utils';
import { Club } from '@/pages/clubs/index';
import { getClubPlayerColumns } from '@/pages/clubs/players/partials/columns';
import { Player } from '@/pages/players/index';
import type { BreadcrumbItem, PageFilters, PaginationLink } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { format } from 'date-fns';
import { CalendarIcon, Plus } from 'lucide-react';
import { useState } from 'react';

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
    filters: PageFilters;
};

export default function ClubPlayers({ club, memberships, availablePlayers, filters }: ClubPlayersProps) {
    const { data: membershipData, links } = memberships;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Clubs', href: '/manage/clubs' },
        { title: club.name, href: `/manage/clubs/${club.id}` },
        { title: 'Players', href: `/manage/clubs/${club.id}/players` },
    ];

    const [assignOpen, setAssignOpen] = useState(false);
    const [startDateOpen, setStartDateOpen] = useState(false);
    const today = new Date();
    const [startDate, setStartDate] = useState<Date>(today);

    const assignForm = useForm({
        player_id: '',
        started_at: format(today, 'yyyy-MM-dd'),
        belt_rank: '' as BeltRank | '',
        grade: '',
    });

    const handleAssign = (e: React.FormEvent) => {
        e.preventDefault();
        assignForm.post(`/manage/clubs/${club.id}/players`, {
            onSuccess: () => {
                setAssignOpen(false);
                setStartDate(today);
                assignForm.reset();
                assignForm.setData('started_at', format(today, 'yyyy-MM-dd'));
            },
        });
    };

    const [membershipToEnd, setMembershipToEnd] = useState<ClubMembership | null>(null);
    const [endDate, setEndDate] = useState<Date | undefined>();
    const [endDateOpen, setEndDateOpen] = useState(false);

    const endForm = useForm({ ended_at: '' });

    const handleEndMembership = (e: React.FormEvent) => {
        e.preventDefault();
        if (!membershipToEnd) {
            return;
        }
        endForm.put(`/manage/clubs/${club.id}/players/${membershipToEnd.id}`, {
            onSuccess: () => {
                setMembershipToEnd(null);
                setEndDate(undefined);
                endForm.reset();
            },
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
                <div className="flex items-center justify-between">
                    <HeadingSmall title="Players" description={`Manage player memberships for ${club.name}.`} />
                    <Button variant="default" onClick={() => setAssignOpen(true)}>
                        <Plus className="mr-2 h-4 w-4" />
                        Assign Player
                    </Button>
                </div>

                <div className="mt-4">
                    <DataTable columns={columns} data={membershipData} />
                    <DataTablePagination
                        links={links as PaginationLink[]}
                        perPage={filters.per_page ?? 15}
                        onPageChange={() => {}}
                        onPerPageCountUpdated={() => {}}
                    />
                </div>
            </ClubLayout>

            <Dialog open={assignOpen} onOpenChange={(open) => !open && setAssignOpen(false)}>
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
                                    {availablePlayers.map((player) => (
                                        <SelectItem key={player.id} value={player.id}>
                                            {player.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={assignForm.errors.player_id} />
                        </div>

                        <div>
                            <Label>Start Date</Label>
                            <Popover open={startDateOpen} onOpenChange={setStartDateOpen}>
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
                            <Label htmlFor="belt_rank">
                                Belt Rank <span className="text-xs font-light">(Optional)</span>
                            </Label>
                            <Select
                                value={assignForm.data.belt_rank === '' ? '' : String(assignForm.data.belt_rank)}
                                onValueChange={(value) =>
                                    assignForm.setData('belt_rank', value === '' ? '' : (parseInt(value) as BeltRank))
                                }
                            >
                                <SelectTrigger className="mt-1 w-full">
                                    <SelectValue placeholder="Select belt rank" />
                                </SelectTrigger>
                                <SelectContent>
                                    {(Object.entries(BELT_RANK_LABELS) as [string, string][]).map(([value, label]) => (
                                        <SelectItem key={value} value={value}>
                                            {label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={assignForm.errors.belt_rank} />
                        </div>

                        <div>
                            <Label htmlFor="grade">
                                Grade <span className="text-xs font-light">(Optional)</span>
                            </Label>
                            <Input
                                id="grade"
                                value={assignForm.data.grade}
                                onChange={(e) => assignForm.setData('grade', e.target.value)}
                                placeholder="e.g. 1st dan"
                                className="mt-1 block w-full"
                            />
                            <InputError message={assignForm.errors.grade} />
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setAssignOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={assignForm.processing}>
                                {assignForm.processing ? 'Assigning...' : 'Assign'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={!!membershipToEnd} onOpenChange={(open) => !open && setMembershipToEnd(null)}>
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
                            <Popover open={endDateOpen} onOpenChange={setEndDateOpen}>
                                <PopoverTrigger asChild>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className={cn('mt-1 w-full justify-start text-left font-normal', !endDate && 'text-muted-foreground')}
                                    >
                                        <CalendarIcon className="mr-2 h-4 w-4" />
                                        {endDate ? format(endDate, 'PPP') : <span>Pick a date</span>}
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
                                            return date < new Date(membershipToEnd.started_at) || date > new Date();
                                        }}
                                        initialFocus
                                    />
                                </PopoverContent>
                            </Popover>
                            <InputError message={endForm.errors.ended_at} />
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setMembershipToEnd(null)}>
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
