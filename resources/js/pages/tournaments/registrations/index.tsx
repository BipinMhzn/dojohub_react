import HeadingSmall from '@/components/heading-small';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import CategoryLayout from '@/layouts/category/layout';
import { Player } from '@/pages/players';
import {
    CategoryStatus,
    CoachOption,
    PaymentMethod,
    paymentMethodLabel,
    RegistrationStatus,
    Tournament,
    TournamentCategory,
    TournamentRegistration,
} from '@/pages/tournaments';
import { getCategoryDisplayName, managerTransitions, transitionCategory } from '@/pages/tournaments/partials/category-columns';
import type { BreadcrumbItem } from '@/types';
import { formatUTCDate } from '@/utils/date';
import { Head, Link, router } from '@inertiajs/react';
import { Scale, Trash2, UserMinus, UserPlus, Users, Wallet } from 'lucide-react';
import React, { useState } from 'react';

type PlayerClub = { id: string; name: string };
type EligiblePlayer = Player & { clubs?: PlayerClub[] };

interface Props {
    tournament: Tournament;
    category: TournamentCategory;
    registrations: TournamentRegistration[];
    players: EligiblePlayer[];
    coaches: CoachOption[];
}

function calculateAge(dob: string): number {
    const birth = new Date(dob);
    const today = new Date();
    let age = today.getFullYear() - birth.getFullYear();
    if (today.getMonth() < birth.getMonth() || (today.getMonth() === birth.getMonth() && today.getDate() < birth.getDate())) {
        age--;
    }
    return age;
}

export default function TournamentRegistrations({ tournament, category, registrations, players, coaches }: Props) {
    const categoryName = getCategoryDisplayName(category);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Tournaments', href: '/manage/tournaments' },
        { title: tournament.name, href: `/manage/tournaments/${tournament.id}` },
        { title: categoryName, href: `/manage/tournaments/${tournament.id}/categories/${category.id}/edit` },
        { title: 'Registrations', href: `/manage/tournaments/${tournament.id}/categories/${category.id}/registrations` },
    ];

    const statusNum = Number(category.status);
    const isTerminal = [CategoryStatus.Completed, CategoryStatus.Cancelled].includes(statusNum as CategoryStatus);
    const canRegister = statusNum === CategoryStatus.RegistrationOpen;
    // Withdrawal is allowed in any non-terminal state. Once a bracket exists it stays allowed but
    // warns that it may leave unfilled match slots (lifecycle plan §5.5).
    const canWithdraw = !isTerminal;
    const bracketGenerated = [CategoryStatus.BracketGenerated, CategoryStatus.InProgress].includes(statusNum as CategoryStatus);
    const canRecordWeighIn = [CategoryStatus.RegistrationClosed, CategoryStatus.BracketGenerated].includes(statusNum as CategoryStatus);

    // Registration open/close/re-open controls (cancellation stays in the category table menu).
    const registrationTransitions = (managerTransitions[statusNum] ?? []).filter((t) => t.target !== CategoryStatus.Cancelled);

    const canManageExtras = !isTerminal;

    const feeAmount = category.fee ? Number(category.fee.amount) : null;
    const feeCurrency = category.fee?.currency ?? 'NPR';

    const totalPaid = (reg: TournamentRegistration): number => (reg.payments ?? []).reduce((sum, p) => sum + Number(p.amount_paid), 0);

    const showActions = registrations.length > 0 && !isTerminal;
    const columnCount = 7 + (showActions ? 1 : 0);

    const [selectedPlayerId, setSelectedPlayerId] = useState<string | null>(null);
    const [selectedClubId, setSelectedClubId] = useState<string | null>(null);
    const [dialogOpen, setDialogOpen] = useState(false);

    const selectedPlayer = players.find((p) => p.id === selectedPlayerId) ?? null;
    const playerClubs = selectedPlayer?.clubs ?? [];
    // A player with active clubs must register under one of them; a player with none registers as independent.
    const canSubmitRegistration = !!selectedPlayerId && (playerClubs.length === 0 || !!selectedClubId);

    function handlePlayerChange(playerId: string) {
        setSelectedPlayerId(playerId);
        const clubs = players.find((p) => p.id === playerId)?.clubs ?? [];
        setSelectedClubId(clubs.length === 1 ? clubs[0].id : null);
    }

    const [weighDialogOpen, setWeighDialogOpen] = useState(false);
    const [weighReg, setWeighReg] = useState<TournamentRegistration | null>(null);
    const [weight, setWeight] = useState('');
    const [weighNotes, setWeighNotes] = useState('');

    const [regToWithdraw, setRegToWithdraw] = useState<TournamentRegistration | null>(null);

    function handleConfirmWithdraw() {
        if (!regToWithdraw) return;

        router.post(
            `/manage/tournaments/${tournament.id}/categories/${category.id}/registrations/${regToWithdraw.id}/withdraw`,
            {},
            { onFinish: () => setRegToWithdraw(null) },
        );
    }

    // Coach management dialog
    const [coachReg, setCoachReg] = useState<TournamentRegistration | null>(null);
    const [assignCoachId, setAssignCoachId] = useState<string | null>(null);

    // Payment dialog
    const [payReg, setPayReg] = useState<TournamentRegistration | null>(null);
    const [payAmount, setPayAmount] = useState('');
    const [payCurrency, setPayCurrency] = useState(feeCurrency);
    const [payMethod, setPayMethod] = useState(String(PaymentMethod.Cash));
    const [payReference, setPayReference] = useState('');

    // After a partial reload the prop array is replaced, so re-derive the open dialog's
    // registration by id to keep its coaches/payments lists fresh.
    const coachRegLive = coachReg ? (registrations.find((r) => r.id === coachReg.id) ?? coachReg) : null;
    const payRegLive = payReg ? (registrations.find((r) => r.id === payReg.id) ?? payReg) : null;

    const assignedCoachIds = new Set((coachRegLive?.coaches ?? []).map((c) => c.coach_id));
    const availableCoaches = coaches.filter((c) => !assignedCoachIds.has(c.id));

    function openPayments(reg: TournamentRegistration) {
        setPayReg(reg);
        const due = feeAmount !== null ? Math.max(feeAmount - totalPaid(reg), 0) : null;
        setPayAmount(due !== null && due > 0 ? due.toFixed(2) : '');
        setPayCurrency(feeCurrency);
        setPayMethod(String(PaymentMethod.Cash));
        setPayReference('');
    }

    function handleAssignCoach() {
        if (!coachReg || !assignCoachId) return;

        router.post(
            `/manage/tournaments/${tournament.id}/categories/${category.id}/registrations/${coachReg.id}/coaches`,
            { coach_id: assignCoachId },
            { preserveScroll: true, preserveState: true, onSuccess: () => setAssignCoachId(null) },
        );
    }

    function handleRemoveCoach(coachId: string) {
        if (!coachReg) return;

        router.delete(`/manage/tournaments/${tournament.id}/categories/${category.id}/registrations/${coachReg.id}/coaches/${coachId}`, {
            preserveScroll: true,
            preserveState: true,
        });
    }

    function handleDeletePayment(paymentId: string) {
        if (!payReg) return;

        router.delete(`/manage/tournaments/${tournament.id}/categories/${category.id}/registrations/${payReg.id}/payments/${paymentId}`, {
            preserveScroll: true,
            preserveState: true,
        });
    }

    function handleRecordPayment(e: React.FormEvent) {
        e.preventDefault();
        if (!payReg || !payAmount) return;

        router.post(
            `/manage/tournaments/${tournament.id}/categories/${category.id}/registrations/${payReg.id}/payments`,
            { amount_paid: payAmount, currency: payCurrency, method: payMethod, reference: payReference },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    setPayAmount('');
                    setPayReference('');
                },
            },
        );
    }

    function handleRegister(e: React.FormEvent) {
        e.preventDefault();
        if (!canSubmitRegistration || !selectedPlayerId) return;

        router.post(
            `/manage/tournaments/${tournament.id}/categories/${category.id}/registrations`,
            { player_id: selectedPlayerId, club_id: selectedClubId },
            {
                onSuccess: () => {
                    setSelectedPlayerId(null);
                    setSelectedClubId(null);
                    setDialogOpen(false);
                },
            },
        );
    }

    function openWeighIn(reg: TournamentRegistration) {
        setWeighReg(reg);
        // Pre-fill with the player's current weight; the official can adjust it if it has changed.
        setWeight(reg.player?.weight != null ? String(reg.player.weight) : '');
        setWeighNotes('');
        setWeighDialogOpen(true);
    }

    function handleWeighIn(e: React.FormEvent) {
        e.preventDefault();
        if (!weighReg || !weight) return;

        router.post(
            `/manage/tournaments/${tournament.id}/categories/${category.id}/weigh-ins`,
            { registration_id: weighReg.id, weight, notes: weighNotes },
            {
                onSuccess: () => {
                    setWeighDialogOpen(false);
                    setWeighReg(null);
                },
            },
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${categoryName} – Registrations`} />

            <CategoryLayout tournament={tournament} category={category} activeKey="registrations">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <HeadingSmall
                        title="Registrations & Weigh-ins"
                        description={`${registrations.length} player${registrations.length !== 1 ? 's' : ''} registered`}
                    />
                    <div className="flex flex-wrap items-center gap-2">
                        {registrationTransitions.map((t) => (
                            <Button
                                key={t.target}
                                variant="outline"
                                size="sm"
                                onClick={() => transitionCategory(tournament.id, category.id, t.target)}
                            >
                                {t.label}
                            </Button>
                        ))}
                        {canRegister && (
                            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                                <DialogTrigger asChild>
                                    <Button size="sm">
                                        <UserPlus className="mr-2 size-4" />
                                        Register Player
                                    </Button>
                                </DialogTrigger>
                                <DialogContent className="sm:max-w-md">
                                    <DialogHeader>
                                        <DialogTitle>Register Player</DialogTitle>
                                        <DialogDescription>Select an eligible player and their club.</DialogDescription>
                                    </DialogHeader>
                                    <form onSubmit={handleRegister} className="space-y-4">
                                        <div className="space-y-4">
                                            <div className="space-y-2">
                                                <label className="text-sm font-medium">Player</label>
                                                <Select value={selectedPlayerId ?? ''} onValueChange={handlePlayerChange}>
                                                    <SelectTrigger className="w-full">
                                                        <SelectValue placeholder="Select player" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {players.length > 0 ? (
                                                            players.map((player) => (
                                                                <SelectItem key={player.id} value={player.id}>
                                                                    <div className="flex items-center gap-2">
                                                                        <Avatar className="h-6 w-6">
                                                                            <AvatarImage
                                                                                src={player.profile_photo_url ?? undefined}
                                                                                alt={player.name}
                                                                            />
                                                                            <AvatarFallback>{player.name[0]}</AvatarFallback>
                                                                        </Avatar>
                                                                        <span>
                                                                            {player.name} — Age: {calculateAge(player.dob)}, {player.weight} kg
                                                                        </span>
                                                                    </div>
                                                                </SelectItem>
                                                            ))
                                                        ) : (
                                                            <div className="text-muted-foreground px-3 py-2 text-sm">
                                                                No eligible players available
                                                            </div>
                                                        )}
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div className="space-y-2">
                                                <label className="text-sm font-medium">Club</label>
                                                {!selectedPlayerId ? (
                                                    <p className="text-muted-foreground text-sm">Select a player to see their club.</p>
                                                ) : playerClubs.length === 0 ? (
                                                    <p className="text-muted-foreground text-sm">
                                                        This player has no active club — they will be registered as an independent competitor.
                                                    </p>
                                                ) : playerClubs.length === 1 ? (
                                                    <div className="border-input bg-muted/40 text-foreground flex h-9 items-center rounded-md border px-3 text-sm">
                                                        {playerClubs[0].name}
                                                    </div>
                                                ) : (
                                                    <Select value={selectedClubId ?? ''} onValueChange={setSelectedClubId}>
                                                        <SelectTrigger className="w-full">
                                                            <SelectValue placeholder="Select club" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {playerClubs.map((club) => (
                                                                <SelectItem key={club.id} value={club.id}>
                                                                    {club.name}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                )}
                                            </div>
                                        </div>

                                        <DialogFooter>
                                            <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>
                                                Cancel
                                            </Button>
                                            <Button type="submit" disabled={!canSubmitRegistration}>
                                                Register
                                            </Button>
                                        </DialogFooter>
                                    </form>
                                </DialogContent>
                            </Dialog>
                        )}
                    </div>
                </div>

                {!canRecordWeighIn && !isTerminal && registrations.length > 0 && (
                    <p className="text-muted-foreground mt-2 text-sm">
                        Weigh-ins open once you <span className="font-medium">close registration</span>. Use the button above, then record weights per
                        competitor here.
                    </p>
                )}

                <Table className="mt-4">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Player</TableHead>
                            <TableHead>Club</TableHead>
                            <TableHead>Age (at reg.)</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Weigh-in</TableHead>
                            <TableHead>Coaches</TableHead>
                            <TableHead>Paid</TableHead>
                            {showActions && <TableHead>Actions</TableHead>}
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {registrations.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={columnCount} className="text-muted-foreground text-center">
                                    No players registered yet.
                                </TableCell>
                            </TableRow>
                        ) : (
                            registrations.map((reg) => {
                                const isWithdrawn = Number(reg.status) === RegistrationStatus.Withdrawn;
                                const latestWeighIn = reg.weigh_ins?.[0] ?? null;

                                return (
                                    <TableRow key={reg.id} className={isWithdrawn ? 'opacity-50' : ''}>
                                        <TableCell>
                                            <div className="flex items-center gap-3">
                                                <Avatar className="h-8 w-8">
                                                    <AvatarImage src={reg.player?.profile_photo_url ?? undefined} alt={reg.player?.name} />
                                                    <AvatarFallback>
                                                        {reg.player?.name
                                                            .split(' ')
                                                            .map((n) => n[0])
                                                            .join('')
                                                            .toUpperCase()
                                                            .slice(0, 2)}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <span>{reg.player?.name}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center gap-3">
                                                <span className={reg.club?.name ? '' : 'text-muted-foreground italic'}>
                                                    {reg.club?.name ?? 'Independent'}
                                                </span>
                                            </div>
                                        </TableCell>
                                        <TableCell>{reg.player_age}</TableCell>
                                        <TableCell>
                                            <span
                                                className={`text-xs font-medium ${Number(reg.status) === RegistrationStatus.Confirmed ? 'text-green-600 dark:text-green-400' : isWithdrawn ? 'text-red-500 dark:text-red-400' : 'text-muted-foreground'}`}
                                            >
                                                {Number(reg.status) === RegistrationStatus.Registered
                                                    ? 'Registered'
                                                    : Number(reg.status) === RegistrationStatus.Confirmed
                                                      ? 'Confirmed'
                                                      : 'Withdrawn'}
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            {latestWeighIn ? (
                                                <div className="flex flex-col gap-1">
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-medium">{latestWeighIn.weight} kg</span>
                                                        <Badge
                                                            className={
                                                                latestWeighIn.made_weight
                                                                    ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
                                                                    : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
                                                            }
                                                        >
                                                            {latestWeighIn.made_weight ? 'Made weight' : 'Failed'}
                                                        </Badge>
                                                    </div>
                                                    <span className="text-muted-foreground text-xs">
                                                        {formatUTCDate(latestWeighIn.weighed_at)}
                                                        {(reg.weigh_ins?.length ?? 0) > 1 ? ` · ${reg.weigh_ins?.length} attempts` : ''}
                                                    </span>
                                                </div>
                                            ) : (
                                                <span className="text-muted-foreground">—</span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {(reg.coaches?.length ?? 0) > 0 ? (
                                                <div className="flex flex-col gap-0.5">
                                                    {reg.coaches?.map((c) => (
                                                        <span key={c.id} className="text-sm">
                                                            {c.coach?.full_name ?? 'Coach'}
                                                        </span>
                                                    ))}
                                                </div>
                                            ) : (
                                                <span className="text-muted-foreground">—</span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {(() => {
                                                const paid = totalPaid(reg);
                                                if (feeAmount === null && paid === 0) {
                                                    return <span className="text-muted-foreground">—</span>;
                                                }
                                                const fullyPaid = feeAmount !== null && paid >= feeAmount;
                                                return (
                                                    <div className="flex items-center gap-2">
                                                        <span className="text-sm">
                                                            {paid.toFixed(2)}
                                                            {feeAmount !== null ? ` / ${feeAmount.toFixed(2)}` : ''} {feeCurrency}
                                                        </span>
                                                        {feeAmount !== null && (
                                                            <Badge
                                                                className={
                                                                    fullyPaid
                                                                        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
                                                                        : 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300'
                                                                }
                                                            >
                                                                {fullyPaid ? 'Paid' : 'Due'}
                                                            </Badge>
                                                        )}
                                                    </div>
                                                );
                                            })()}
                                        </TableCell>
                                        {showActions && (
                                            <TableCell>
                                                <div className="flex gap-2">
                                                    {!isWithdrawn && (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            disabled={!canRecordWeighIn}
                                                            onClick={() => openWeighIn(reg)}
                                                            title={canRecordWeighIn ? 'Record weigh-in' : 'Close registration to begin weigh-ins'}
                                                        >
                                                            <Scale size={16} />
                                                        </Button>
                                                    )}
                                                    {canManageExtras && !isWithdrawn && (
                                                        <Button variant="outline" size="sm" title="Manage coaches" onClick={() => setCoachReg(reg)}>
                                                            <Users size={16} />
                                                        </Button>
                                                    )}
                                                    {canManageExtras && !isWithdrawn && (
                                                        <Button variant="outline" size="sm" title="Payments" onClick={() => openPayments(reg)}>
                                                            <Wallet size={16} />
                                                        </Button>
                                                    )}
                                                    {canWithdraw && !isWithdrawn && (
                                                        <Button variant="outline" size="sm" title="Withdraw" onClick={() => setRegToWithdraw(reg)}>
                                                            <UserMinus size={16} />
                                                        </Button>
                                                    )}
                                                </div>
                                            </TableCell>
                                        )}
                                    </TableRow>
                                );
                            })
                        )}
                    </TableBody>
                </Table>
            </CategoryLayout>

            <Dialog open={weighDialogOpen} onOpenChange={setWeighDialogOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Record Weigh-in</DialogTitle>
                        <DialogDescription>
                            {weighReg?.player?.name} — category bounds {category.min_weight}–{category.max_weight} kg.
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleWeighIn} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="weight">Weight (kg)</Label>
                            <Input
                                id="weight"
                                type="number"
                                step="0.01"
                                min="0"
                                value={weight}
                                onChange={(e) => setWeight(e.target.value)}
                                placeholder="e.g. 72.50"
                                autoFocus
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="weigh-notes">Notes (optional)</Label>
                            <Input id="weigh-notes" type="text" value={weighNotes} onChange={(e) => setWeighNotes(e.target.value)} />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setWeighDialogOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={!weight}>
                                Record Weigh-in
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={!!regToWithdraw} onOpenChange={(open) => !open && setRegToWithdraw(null)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Withdraw Competitor</DialogTitle>
                        <DialogDescription>
                            Withdraw <span className="font-medium">{regToWithdraw?.player?.name}</span> from {categoryName}? They will no longer be
                            eligible for the bracket. This can be reversed only by re-registering them.
                        </DialogDescription>
                    </DialogHeader>

                    {bracketGenerated && (
                        <div className="rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-900/20 dark:text-amber-300">
                            The bracket has already been generated. Withdrawing this competitor may leave unfilled match slots.
                        </div>
                    )}
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setRegToWithdraw(null)}>
                            Cancel
                        </Button>
                        <Button variant="destructive" onClick={handleConfirmWithdraw}>
                            Withdraw
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={!!coachReg} onOpenChange={(open) => !open && setCoachReg(null)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Coaches</DialogTitle>
                        <DialogDescription>Coaches accompanying {coachRegLive?.player?.name} in this category.</DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4">
                        <div className="space-y-2">
                            {(coachRegLive?.coaches?.length ?? 0) > 0 ? (
                                coachRegLive?.coaches?.map((c) => (
                                    <div key={c.id} className="flex items-center justify-between rounded-md border px-3 py-2">
                                        <span className="text-sm">{c.coach?.full_name ?? 'Coach'}</span>
                                        <Button variant="ghost" size="sm" title="Remove coach" onClick={() => handleRemoveCoach(c.coach_id)}>
                                            <Trash2 size={16} />
                                        </Button>
                                    </div>
                                ))
                            ) : (
                                <p className="text-muted-foreground text-sm">No coaches assigned yet.</p>
                            )}
                        </div>

                        <div className="border-t pt-4">
                            {coaches.length === 0 ? (
                                <div className="rounded-md border border-dashed px-3 py-4 text-center">
                                    <p className="text-muted-foreground text-sm">No coaches have been added yet.</p>
                                    <Link href="/manage/coaches" className="text-primary mt-1 inline-block text-sm font-medium hover:underline">
                                        Create a coach first
                                    </Link>
                                </div>
                            ) : availableCoaches.length === 0 ? (
                                <p className="text-muted-foreground text-sm">All available coaches are already assigned to this competitor.</p>
                            ) : (
                                <div className="flex items-end gap-2">
                                    <div className="flex-1 space-y-2">
                                        <Label>Add a coach</Label>
                                        <Select value={assignCoachId ?? ''} onValueChange={setAssignCoachId}>
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Select coach" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {availableCoaches.map((c) => (
                                                    <SelectItem key={c.id} value={c.id}>
                                                        {c.full_name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <Button onClick={handleAssignCoach} disabled={!assignCoachId}>
                                        Assign
                                    </Button>
                                </div>
                            )}
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setCoachReg(null)}>
                            Done
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={!!payReg} onOpenChange={(open) => !open && setPayReg(null)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Payments</DialogTitle>
                        <DialogDescription>
                            {payRegLive?.player?.name}
                            {feeAmount !== null
                                ? ` — fee ${feeAmount.toFixed(2)} ${feeCurrency}, paid ${payRegLive ? totalPaid(payRegLive).toFixed(2) : '0.00'} ${feeCurrency}`
                                : ' — no fee set for this category'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4">
                        {(payRegLive?.payments?.length ?? 0) > 0 ? (
                            <div className="space-y-2">
                                {payRegLive?.payments?.map((p) => (
                                    <div key={p.id} className="flex items-center justify-between rounded-md border px-3 py-2 text-sm">
                                        <div className="flex flex-col">
                                            <span className="font-medium">
                                                {Number(p.amount_paid).toFixed(2)} {p.currency}
                                            </span>
                                            <span className="text-muted-foreground text-xs">
                                                {paymentMethodLabel[Number(p.method)]}
                                                {p.reference ? ` · ${p.reference}` : ''}
                                                {p.paid_at ? ` · ${formatUTCDate(p.paid_at)}` : ''}
                                            </span>
                                        </div>
                                        <Button variant="ghost" size="sm" title="Delete payment" onClick={() => handleDeletePayment(p.id)}>
                                            <Trash2 size={16} />
                                        </Button>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-muted-foreground text-sm">No payments recorded yet.</p>
                        )}

                        <form onSubmit={handleRecordPayment} className="space-y-4 border-t pt-4">
                            <div className="flex gap-2">
                                <div className="flex-1 space-y-2">
                                    <Label htmlFor="pay-amount">Amount</Label>
                                    <Input
                                        id="pay-amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={payAmount}
                                        onChange={(e) => setPayAmount(e.target.value)}
                                        placeholder="0.00"
                                        autoFocus
                                    />
                                </div>
                                <div className="w-24 space-y-2">
                                    <Label htmlFor="pay-currency">Currency</Label>
                                    <Input
                                        id="pay-currency"
                                        value={payCurrency}
                                        onChange={(e) => setPayCurrency(e.target.value.toUpperCase())}
                                        maxLength={3}
                                        className="uppercase"
                                    />
                                </div>
                            </div>
                            <div className="flex gap-2">
                                <div className="flex-1 space-y-2">
                                    <Label>Method</Label>
                                    <Select value={payMethod} onValueChange={setPayMethod}>
                                        <SelectTrigger className="w-full">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {Object.entries(paymentMethodLabel).map(([value, label]) => (
                                                <SelectItem key={value} value={value}>
                                                    {label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="flex-1 space-y-2">
                                    <Label htmlFor="pay-reference">Reference (optional)</Label>
                                    <Input id="pay-reference" value={payReference} onChange={(e) => setPayReference(e.target.value)} />
                                </div>
                            </div>
                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setPayReg(null)}>
                                    Close
                                </Button>
                                <Button type="submit" disabled={!payAmount}>
                                    Record Payment
                                </Button>
                            </DialogFooter>
                        </form>
                    </div>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
