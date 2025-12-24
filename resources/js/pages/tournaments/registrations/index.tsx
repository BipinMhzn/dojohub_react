import HeadingSmall from '@/components/heading-small';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { Club } from '@/pages/clubs';
import { Player } from '@/pages/players';
import TournamentHeader from '@/pages/tournaments/partials/tournament-header';
import TournamentTabs from '@/pages/tournaments/partials/tournament-tabs';
import { Head, router } from '@inertiajs/react';
import { Trash2, UserPlus } from 'lucide-react';
import React, { useState } from 'react';
import { Tournament, TournamentStatus } from '../index';

export type tournamentRegistration = {
    id: string;
    tournament_id: string;
    club_id: string;
    player_id: string;
    player_age: number;
    player_weight: number;
    player: Player;
    club: Club;
    [key: string]: unknown;
};

interface Props {
    tournament: Tournament;
    players: Player[];
    clubs: Club[];
    registrations: tournamentRegistration[];
}

export default function TournamentRegistrations({ tournament, registrations, players, clubs }: Props) {
    const breadcrumbs = [
        {
            title: 'Tournaments',
            href: '/manage/tournaments',
        },
        {
            title: 'Details',
            href: `/manage/tournaments/${tournament.id}`,
        },
    ];

    function calculateAge(dob: string) {
        const birth = new Date(dob);
        const today = new Date();
        let age = today.getFullYear() - birth.getFullYear();
        if (today.getMonth() < birth.getMonth() || (today.getMonth() === birth.getMonth() && today.getDate() < birth.getDate())) {
            age--;
        }
        return age;
    }

    const [selectedPlayerId, setSelectedPlayerId] = useState<string | null>(null);
    const [selectedClubId, setSelectedClubId] = useState<string | null>(null);
    const [dialogOpen, setDialogOpen] = useState(false);
    const registeredPlayerIds = registrations.map((reg) => reg.player_id);

    const eligiblePlayers = players.filter((p) => !registeredPlayerIds.includes(p.id));

    function handleRegister(e: React.FormEvent) {
        e.preventDefault();

        if (!selectedPlayerId || !selectedClubId) return;

        router.post(
            `/manage/tournaments/${tournament.id}/registrations`,
            {
                player_id: selectedPlayerId,
                club_id: selectedClubId,
            },
            {
                onSuccess: () => {
                    setSelectedPlayerId(null);
                    setSelectedClubId(null);
                    setDialogOpen(false);
                },
            },
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Tournament Detail: ${tournament.name}`} />

            <div className="px-4 py-6">
                <TournamentHeader tournament={tournament} />
                <TournamentTabs tournamentId={tournament.id} activeTab="players" />

                <div className="mb-6">
                    <div className="flex items-center justify-between">
                        <HeadingSmall
                            title="Registered Players"
                            description={`${registrations.length} player${registrations.length !== 1 ? 's' : ''} registered`}
                        />
                        {tournament.status === TournamentStatus.SCHEDULED && (
                            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                                <DialogTrigger asChild>
                                    <Button>
                                        <UserPlus className="mr-2 size-4" />
                                        Register Player
                                    </Button>
                                </DialogTrigger>
                                <DialogContent className="sm:max-w-md">
                                    <DialogHeader>
                                        <DialogTitle>Register Player</DialogTitle>
                                        <DialogDescription>
                                            Select a player and their club to register for this tournament.
                                        </DialogDescription>
                                    </DialogHeader>
                                    <form onSubmit={handleRegister} className="space-y-4">
                                        <div className="space-y-4">
                                            <div className="space-y-2">
                                                <label className="text-sm font-medium">Player</label>
                                                <Select value={selectedPlayerId ?? ''} onValueChange={setSelectedPlayerId}>
                                                    <SelectTrigger className="w-full">
                                                        <SelectValue placeholder="Select player" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {eligiblePlayers.length > 0 ? (
                                                            eligiblePlayers.map((player) => (
                                                                <SelectItem key={player.id} value={player.id}>
                                                                    <div className="flex items-center gap-2">
                                                                        <Avatar className="h-6 w-6">
                                                                            <AvatarImage src={player.profile_photo_url ?? undefined} alt={player.name} />
                                                                            <AvatarFallback>{player.name[0]}</AvatarFallback>
                                                                        </Avatar>
                                                                        <span>
                                                                            {player.name} — Age: {calculateAge(player.dob)}, Weight: {player.weight} Kg
                                                                        </span>
                                                                    </div>
                                                                </SelectItem>
                                                            ))
                                                        ) : (
                                                            <div className="text-muted-foreground px-3 py-2 text-sm">All players are registered</div>
                                                        )}
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div className="space-y-2">
                                                <label className="text-sm font-medium">Club</label>
                                                <Select value={selectedClubId ?? ''} onValueChange={setSelectedClubId}>
                                                    <SelectTrigger className="w-full">
                                                        <SelectValue placeholder="Select club" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {clubs.map((club) => (
                                                            <SelectItem key={club.id} value={club.id}>
                                                                <div className="flex items-center gap-2">
                                                                    <Avatar className="h-6 w-6">
                                                                        <AvatarImage src={club.logo_url ?? undefined} alt={club.name} />
                                                                        <AvatarFallback>{club.name[0]}</AvatarFallback>
                                                                    </Avatar>
                                                                    <span>{club.name}</span>
                                                                </div>
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                        </div>

                                        <DialogFooter>
                                            <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>
                                                Cancel
                                            </Button>
                                            <Button type="submit" disabled={!selectedPlayerId || !selectedClubId}>
                                                Register
                                            </Button>
                                        </DialogFooter>
                                    </form>
                                </DialogContent>
                            </Dialog>
                        )}
                    </div>

                    <Table className="mt-4">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Player</TableHead>
                                <TableHead>Club</TableHead>
                                <TableHead>Age</TableHead>
                                <TableHead>Weight (Kg)</TableHead>
                                {tournament.status === TournamentStatus.SCHEDULED && <TableHead>Actions</TableHead>}
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {registrations.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={5} className="text-muted-foreground text-center">
                                        No players registered yet.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                registrations.map((reg) => (
                                    <TableRow key={reg.id}>
                                        <TableCell>
                                            <div className="flex items-center gap-3">
                                                <Avatar className="h-8 w-8">
                                                    <AvatarImage src={reg.player.profile_photo_url ?? undefined} alt={reg.player.name} />
                                                    <AvatarFallback>
                                                        {reg.player.name
                                                            .split(' ')
                                                            .map((n) => n[0])
                                                            .join('')
                                                            .toUpperCase()
                                                            .slice(0, 2)}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <span>{reg.player.name}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center gap-3">
                                                <Avatar className="h-8 w-8">
                                                    <AvatarImage src={reg.club.logo_url ?? undefined} alt={reg.club.name} />
                                                    <AvatarFallback>
                                                        {reg.club.name
                                                            .split(' ')
                                                            .map((n) => n[0])
                                                            .join('')
                                                            .toUpperCase()
                                                            .slice(0, 2)}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <span>{reg.club.name}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>{reg.player_age}</TableCell>
                                        <TableCell>{reg.player_weight}</TableCell>
                                        {tournament.status === TournamentStatus.SCHEDULED && (
                                            <TableCell>
                                                <Button
                                                    variant="destructive"
                                                    size="sm"
                                                    onClick={() => router.delete(`/manage/tournaments/${tournament.id}/registrations/${reg.id}`)}
                                                >
                                                    <Trash2 size={18} />
                                                </Button>
                                            </TableCell>
                                        )}
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </AppLayout>
    );
}
