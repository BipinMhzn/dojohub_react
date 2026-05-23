import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { DataTable } from '@/components/ui/data-table';
import { DataTableControls } from '@/components/ui/data-table-controls';
import DataTablePagination from '@/components/ui/data-table-pagination';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import ClubLayout from '@/layouts/club/layout';
import { Club } from '@/pages/clubs/index';
import { getClubCoachColumns } from '@/pages/clubs/coaches/partials/columns';
import type { BreadcrumbItem, PageFilters, PaginationLink } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { format } from 'date-fns';
import { CalendarIcon, Plus } from 'lucide-react';
import React, { useEffect, useRef, useState } from 'react';

export type CoachOption = {
    id: string;
    full_name: string;
};

export type ClubCoachMembership = {
    id: string;
    club_id: string;
    coach_id: string;
    coach: CoachOption;
    started_at: string;
    ended_at: string | null;
    created_at: string;
    updated_at: string;
};

type ClubCoachesProps = {
    club: Club;
    memberships: {
        data: ClubCoachMembership[];
        links: PaginationLink[];
    };
    availableCoaches: CoachOption[];
    filters: PageFilters;
};

export default function ClubCoaches({ club, memberships, availableCoaches, filters }: ClubCoachesProps) {
    const { data: membershipData, links } = memberships;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Clubs', href: '/manage/clubs' },
        { title: club.name, href: `/manage/clubs/${club.id}` },
        { title: 'Coaches', href: `/manage/clubs/${club.id}/coaches` },
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
            `/manage/clubs/${club.id}/coaches`,
            { keyword: debouncedSearch, status, sort_by: sortBy, sort_order: sortOrder, page: pageNumber, per_page: perPage },
            { only: ['memberships', 'filters'], preserveState: true, preserveScroll: true, replace: true },
        );
    }, [debouncedSearch, status, sortBy, sortOrder, pageNumber, perPage]);

    const handleSearchChange = (val: string) => { setSearch(val); setPageNumber(1); };
    const handleSortByChange = (val: string) => { setSortBy(val); setPageNumber(1); };
    const handleStatusChange = (val: string) => {
        const next = (val || (status !== 'all' ? 'all' : 'active')) as 'active' | 'ended' | 'all';
        setStatus(next);
        setPageNumber(1);
    };

    const [assignOpen, setAssignOpen] = useState(false);
    const [startDateOpen, setStartDateOpen] = useState(false);
    const today = new Date();
    const [startDate, setStartDate] = useState<Date>(today);

    const assignForm = useForm({
        coach_id: '',
        started_at: format(today, 'yyyy-MM-dd'),
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
        assignForm.post(`/manage/clubs/${club.id}/coaches`, {
            onSuccess: () => closeAssignDialog(),
        });
    };

    const [membershipToEnd, setMembershipToEnd] = useState<ClubCoachMembership | null>(null);
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
        endForm.put(`/manage/clubs/${club.id}/coaches/${membershipToEnd.id}`, {
            onSuccess: () => closeEndMembershipDialog(),
        });
    };

    const columns = getClubCoachColumns({
        onEndMembership: (membership) => {
            setMembershipToEnd(membership);
            setEndDate(undefined);
            endForm.reset();
        },
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${club.name} — Coaches`} />

            <ClubLayout club={club} activeKey="coaches">
                <div>
                    <div className="flex items-center justify-between pb-4">
                        <HeadingSmall title="Coaches" description={`Manage coach memberships for ${club.name}.`} />
                        <Button variant="default" onClick={() => setAssignOpen(true)}>
                            <Plus className="mr-2 h-4 w-4" />
                            Assign Coach
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
                        <DialogTitle>Assign Coach</DialogTitle>
                        <DialogDescription>Add a coach to {club.name}.</DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleAssign} className="space-y-4">
                        <div>
                            <Label htmlFor="coach_id">Coach</Label>
                            <Select
                                value={assignForm.data.coach_id}
                                onValueChange={(value) => assignForm.setData('coach_id', value)}
                            >
                                <SelectTrigger className="mt-1 w-full">
                                    <SelectValue placeholder="Select a coach" />
                                </SelectTrigger>
                                <SelectContent>
                                    {availableCoaches.length === 0 ? (
                                        <div className="px-3 py-6 text-center text-sm text-muted-foreground">
                                            No coaches available to assign. All existing coaches are already active at this club.
                                        </div>
                                    ) : (
                                        availableCoaches.map((coach) => (
                                            <SelectItem key={coach.id} value={coach.id}>
                                                {coach.full_name}
                                            </SelectItem>
                                        ))
                                    )}
                                </SelectContent>
                            </Select>
                            <InputError message={assignForm.errors.coach_id} />
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
                            Set the end date for {membershipToEnd?.coach.full_name}&apos;s coaching role at {club.name}.
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
