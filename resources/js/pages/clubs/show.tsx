import InputError from '@/components/input-error';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { Club } from '@/pages/clubs/index';
import { BeltRank, BELT_RANK_LABELS, ClubMembership } from '@/pages/clubs/players/index';
import { Player } from '@/pages/players/index';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { format } from 'date-fns';
import { CalendarIcon, MapPin, Pencil, Phone, Plus } from 'lucide-react';
import { useState } from 'react';

type ClubShowProps = {
    club: Club;
    memberships: ClubMembership[];
    availablePlayers: Pick<Player, 'id' | 'name'>[];
};

export default function ClubShow({ club, memberships, availablePlayers }: ClubShowProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Clubs', href: '/manage/clubs' },
        { title: club.name, href: `/manage/clubs/${club.id}` },
    ];

    const activeMembers = memberships.filter((m) => !m.ended_at);
    const formerMembers = memberships.filter((m) => !!m.ended_at);

    // ── Assign Player dialog ──────────────────────────────────────
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

    // ── Edit Details dialog ───────────────────────────────────────
    const [membershipToEdit, setMembershipToEdit] = useState<ClubMembership | null>(null);

    const editForm = useForm({
        belt_rank: '' as BeltRank | '',
        grade: '',
    });

    const openEdit = (membership: ClubMembership) => {
        setMembershipToEdit(membership);
        editForm.setData({
            belt_rank: membership.belt_rank ?? '',
            grade: membership.grade ?? '',
        });
    };

    const handleEdit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!membershipToEdit) return;
        editForm.patch(`/manage/clubs/${club.id}/players/${membershipToEdit.id}`, {
            onSuccess: () => {
                setMembershipToEdit(null);
                editForm.reset();
            },
        });
    };

    // ── End Membership dialog ─────────────────────────────────────
    const [membershipToEnd, setMembershipToEnd] = useState<ClubMembership | null>(null);
    const [endDate, setEndDate] = useState<Date | undefined>();
    const [endDateOpen, setEndDateOpen] = useState(false);

    const endForm = useForm({ ended_at: '' });

    const handleEndMembership = (e: React.FormEvent) => {
        e.preventDefault();
        if (!membershipToEnd) return;
        endForm.put(`/manage/clubs/${club.id}/players/${membershipToEnd.id}`, {
            onSuccess: () => {
                setMembershipToEnd(null);
                setEndDate(undefined);
                endForm.reset();
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={club.name} />

            <div className="space-y-6 px-4 py-6">
                {/* ── Club Detail Card ─────────────────────────── */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-start gap-6">
                            {club.logo_url && (
                                <img src={club.logo_url} alt={club.name} className="h-20 w-20 shrink-0 rounded-lg object-cover" />
                            )}
                            <div className="flex-1 space-y-1">
                                <div className="flex items-center gap-3">
                                    <h1 className="text-2xl font-semibold">{club.name}</h1>
                                    <span className="text-muted-foreground text-sm font-medium">{club.abbreviation}</span>
                                </div>
                                {club.address && (
                                    <p className="text-muted-foreground flex items-center gap-1 text-sm">
                                        <MapPin className="h-3.5 w-3.5" />
                                        {[club.address.address, club.address.city, club.address.state, club.address.country]
                                            .filter(Boolean)
                                            .join(', ')}
                                    </p>
                                )}
                                {club.phone_number && (
                                    <p className="text-muted-foreground flex items-center gap-1 text-sm">
                                        <Phone className="h-3.5 w-3.5" />
                                        <a href={`tel:${club.phone_number}`} className="hover:underline">
                                            {club.phone_number}
                                        </a>
                                    </p>
                                )}
                            </div>
                            <Button variant="outline" size="sm" asChild>
                                <Link href={`/manage/clubs/${club.id}/edit`}>
                                    <Pencil className="mr-2 h-3.5 w-3.5" />
                                    Edit
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* ── Members Section ──────────────────────────── */}
                <div>
                    <div className="mb-4 flex items-center">
                        <h2 className="text-lg font-semibold">
                            Members
                            {activeMembers.length > 0 && (
                                <span className="text-muted-foreground ml-2 text-sm font-normal">{activeMembers.length} active</span>
                            )}
                        </h2>
                        <Button variant="default" size="sm" className="ml-auto" onClick={() => setAssignOpen(true)}>
                            <Plus className="mr-2 h-4 w-4" />
                            Assign Player
                        </Button>
                    </div>

                    {memberships.length === 0 ? (
                        <p className="text-muted-foreground py-8 text-center text-sm">No members yet. Assign a player to get started.</p>
                    ) : (
                        <div className="space-y-6">
                            {/* Active */}
                            {activeMembers.length > 0 && (
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                    {activeMembers.map((m) => (
                                        <MemberCard
                                            key={m.id}
                                            membership={m}
                                            onEdit={openEdit}
                                            onEnd={(membership) => {
                                                setMembershipToEnd(membership);
                                                setEndDate(undefined);
                                                endForm.reset();
                                            }}
                                        />
                                    ))}
                                </div>
                            )}

                            {/* Former */}
                            {formerMembers.length > 0 && (
                                <div>
                                    <h3 className="text-muted-foreground mb-3 text-sm font-medium">Former Members</h3>
                                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                        {formerMembers.map((m) => (
                                            <MemberCard key={m.id} membership={m} onEdit={openEdit} />
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>

            {/* ── Assign Player Dialog ───────────────────────────── */}
            <Dialog open={assignOpen} onOpenChange={(open) => !open && setAssignOpen(false)}>
                <DialogContent onInteractOutside={(e) => e.preventDefault()}>
                    <DialogHeader>
                        <DialogTitle>Assign Player</DialogTitle>
                        <DialogDescription>Add a player to {club.name}.</DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleAssign} className="space-y-4">
                        <div>
                            <Label htmlFor="player_id">Player</Label>
                            <Select value={assignForm.data.player_id} onValueChange={(v) => assignForm.setData('player_id', v)}>
                                <SelectTrigger className="mt-1 w-full">
                                    <SelectValue placeholder="Select a player" />
                                </SelectTrigger>
                                <SelectContent>
                                    {availablePlayers.map((p) => (
                                        <SelectItem key={p.id} value={p.id}>
                                            {p.name}
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
                                    <Button type="button" variant="outline" className="mt-1 w-full justify-start text-left font-normal">
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

                        <BeltRankGradeFields
                            beltRank={assignForm.data.belt_rank}
                            grade={assignForm.data.grade}
                            beltRankError={assignForm.errors.belt_rank}
                            gradeError={assignForm.errors.grade}
                            onBeltRankChange={(v) => assignForm.setData('belt_rank', v)}
                            onGradeChange={(v) => assignForm.setData('grade', v)}
                        />

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

            {/* ── Edit Details Dialog ────────────────────────────── */}
            <Dialog open={!!membershipToEdit} onOpenChange={(open) => !open && setMembershipToEdit(null)}>
                <DialogContent onInteractOutside={(e) => e.preventDefault()}>
                    <DialogHeader>
                        <DialogTitle>Edit Details</DialogTitle>
                        <DialogDescription>Update belt rank and grade for {membershipToEdit?.player.name}.</DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleEdit} className="space-y-4">
                        <BeltRankGradeFields
                            beltRank={editForm.data.belt_rank}
                            grade={editForm.data.grade}
                            beltRankError={editForm.errors.belt_rank}
                            gradeError={editForm.errors.grade}
                            onBeltRankChange={(v) => editForm.setData('belt_rank', v)}
                            onGradeChange={(v) => editForm.setData('grade', v)}
                        />
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setMembershipToEdit(null)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={editForm.processing}>
                                {editForm.processing ? 'Saving...' : 'Save'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* ── End Membership Dialog ──────────────────────────── */}
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

// ── Shared belt rank + grade fields ──────────────────────────────
type BeltRankGradeFieldsProps = {
    beltRank: BeltRank | '';
    grade: string;
    beltRankError?: string;
    gradeError?: string;
    onBeltRankChange: (value: BeltRank | '') => void;
    onGradeChange: (value: string) => void;
};

function BeltRankGradeFields({ beltRank, grade, beltRankError, gradeError, onBeltRankChange, onGradeChange }: BeltRankGradeFieldsProps) {
    return (
        <>
            <div>
                <Label>
                    Belt Rank <span className="text-xs font-light">(Optional)</span>
                </Label>
                <Select
                    value={beltRank === '' ? 'none' : String(beltRank)}
                    onValueChange={(v) => onBeltRankChange(v === 'none' ? '' : (parseInt(v) as BeltRank))}
                >
                    <SelectTrigger className="mt-1 w-full">
                        <SelectValue placeholder="Select belt rank" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="none">— None —</SelectItem>
                        {(Object.entries(BELT_RANK_LABELS) as [string, string][]).map(([value, label]) => (
                            <SelectItem key={value} value={value}>
                                {label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={beltRankError} />
            </div>
            <div>
                <Label>
                    Grade <span className="text-xs font-light">(Optional)</span>
                </Label>
                <Input value={grade} onChange={(e) => onGradeChange(e.target.value)} placeholder="e.g. 1st dan" className="mt-1 block w-full" />
                <InputError message={gradeError} />
            </div>
        </>
    );
}

// ── Member card ───────────────────────────────────────────────────
type MemberCardProps = {
    membership: ClubMembership;
    onEdit: (membership: ClubMembership) => void;
    onEnd?: (membership: ClubMembership) => void;
};

function MemberCard({ membership, onEdit, onEnd }: MemberCardProps) {
    const { player, belt_rank, grade, started_at, ended_at } = membership;
    const isActive = !ended_at;

    return (
        <Card className={cn('transition-opacity', !isActive && 'opacity-60')}>
            <CardHeader className="pb-0">
                <div className="flex items-center gap-3">
                    <Avatar className="h-12 w-12">
                        <AvatarImage src={player.profile_photo_url ?? undefined} alt={player.name} />
                        <AvatarFallback>
                            {player.name
                                .split(' ')
                                .map((n) => n[0])
                                .join('')
                                .toUpperCase()
                                .slice(0, 2)}
                        </AvatarFallback>
                    </Avatar>
                    <div className="min-w-0 flex-1">
                        <p className="truncate font-medium">{player.name}</p>
                        {isActive ? (
                            <Badge variant="default" className="mt-0.5 text-xs">
                                Active
                            </Badge>
                        ) : (
                            <Badge variant="secondary" className="mt-0.5 text-xs">
                                Ended
                            </Badge>
                        )}
                    </div>
                </div>
            </CardHeader>

            <CardContent className="pt-3">
                <dl className="text-sm space-y-1">
                    <div className="flex justify-between">
                        <dt className="text-muted-foreground">Belt</dt>
                        <dd>{belt_rank !== null && belt_rank !== undefined ? BELT_RANK_LABELS[belt_rank] : '—'}</dd>
                    </div>
                    <div className="flex justify-between">
                        <dt className="text-muted-foreground">Grade</dt>
                        <dd>{grade ?? '—'}</dd>
                    </div>
                    <div className="flex justify-between">
                        <dt className="text-muted-foreground">Since</dt>
                        <dd>{format(new Date(started_at), 'MMM d, yyyy')}</dd>
                    </div>
                    {ended_at && (
                        <div className="flex justify-between">
                            <dt className="text-muted-foreground">Ended</dt>
                            <dd>{format(new Date(ended_at), 'MMM d, yyyy')}</dd>
                        </div>
                    )}
                </dl>
            </CardContent>

            <CardFooter className="gap-2 pt-0">
                <Button variant="outline" size="sm" onClick={() => onEdit(membership)}>
                    Edit Details
                </Button>
                {isActive && onEnd && (
                    <Button variant="ghost" size="sm" className="text-destructive hover:text-destructive" onClick={() => onEnd(membership)}>
                        End
                    </Button>
                )}
            </CardFooter>
        </Card>
    );
}
