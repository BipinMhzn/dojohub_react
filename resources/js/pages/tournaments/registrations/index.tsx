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
import { CategoryStatus, RegistrationStatus, Tournament, TournamentCategory, TournamentRegistration } from '@/pages/tournaments';
import { getCategoryDisplayName, managerTransitions, transitionCategory } from '@/pages/tournaments/partials/category-columns';
import type { BreadcrumbItem } from '@/types';
import { formatUTCDate } from '@/utils/date';
import { Head, router } from '@inertiajs/react';
import { Scale, UserMinus, UserPlus } from 'lucide-react';
import React, { useState } from 'react';

type PlayerClub = { id: string; name: string };
type EligiblePlayer = Player & { clubs?: PlayerClub[] };

interface Props {
    tournament: Tournament;
    category: TournamentCategory;
    registrations: TournamentRegistration[];
    players: EligiblePlayer[];
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

export default function TournamentRegistrations({ tournament, category, registrations, players }: Props) {
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
    const canWithdraw = [CategoryStatus.RegistrationOpen, CategoryStatus.RegistrationClosed].includes(statusNum as CategoryStatus);
    const canRecordWeighIn = [CategoryStatus.RegistrationClosed, CategoryStatus.BracketGenerated].includes(statusNum as CategoryStatus);

    // Registration open/close/re-open controls (cancellation stays in the category table menu).
    const registrationTransitions = (managerTransitions[statusNum] ?? []).filter((t) => t.target !== CategoryStatus.Cancelled);

    const showActions = registrations.length > 0 && !isTerminal;
    const columnCount = 5 + (showActions ? 1 : 0);

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
        </AppLayout>
    );
}
