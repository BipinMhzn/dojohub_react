import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { Club } from '@/pages/clubs';
import { Player } from '@/pages/players';
import { Head, router } from '@inertiajs/react';
import { format } from 'date-fns';
import React, { useState } from 'react';
import { Tournament, TournamentGender, TournamentLevel, TournamentStatus } from './index';

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

export default function TournamentDetail({ tournament, registrations, players, clubs }: Props) {
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
    const registeredPlayerIds = registrations.map((reg) => reg.player_id);

    const eligiblePlayers = players.filter((p) => !registeredPlayerIds.includes(p.id));

    function handleRegister(e: React.FormEvent) {
        e.preventDefault();

        if (!selectedPlayerId || !selectedClubId) return;

        router.post(`/manage/tournaments/${tournament.id}/registrations`, {
            player_id: selectedPlayerId,
            club_id: selectedClubId,
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Tournament Detail: ${tournament.name}`} />

            <div className="px-4 py-6">
                {/*<Heading title={tournament.name} description={tournament.description ?? undefined} />*/}
                <Card className="mb-6">
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div>
                                <CardTitle className="text-xl">{tournament.name}</CardTitle>
                                <CardDescription>{tournament.description}</CardDescription>
                            </div>
                            {tournament.logo_url && <img src={tournament.logo_url} alt="Tournament Logo" className="h-16 w-16 object-contain" />}
                        </div>
                    </CardHeader>
                    <CardContent className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <strong>Status:</strong> {TournamentStatus[tournament.status]}
                        </div>
                        <div>
                            <strong>Level:</strong> {TournamentLevel[tournament.level]}
                        </div>
                        <div>
                            <strong>Gender:</strong> {TournamentGender[tournament.gender]}
                        </div>
                        <div>
                            <strong>Dates:</strong> {format(new Date(tournament.start_at), 'PPP')} –{' '}
                            {tournament.end_at ? format(new Date(tournament.end_at), 'PPP') : 'TBD'}
                        </div>
                        <div>
                            <strong>Age Category:</strong> {tournament.age_category} ({tournament.min_age}–{tournament.max_age} years)
                        </div>
                        <div>
                            <strong>Weight Category:</strong> {tournament.weight_category} ({tournament.min_weight}–{tournament.max_weight} Kg)
                        </div>
                    </CardContent>
                </Card>

                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle>Registered Players</CardTitle>
                        <CardDescription>{registrations.length} player(s) registered</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Player</TableHead>
                                    <TableHead>Club</TableHead>
                                    <TableHead>Age</TableHead>
                                    <TableHead>Weight (Kg)</TableHead>
                                    <TableHead>Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {registrations.map((reg) => {
                                    return (
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
                                            <TableCell>
                                                <Button
                                                    variant="destructive"
                                                    size="sm"
                                                    onClick={() =>
                                                        router.delete(`/manage/tournaments/${tournament.id}/registrations/${reg.id}`)
                                                    }
                                                >
                                                    Remove
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    );
                                })}

                                {tournament.status === TournamentStatus.SCHEDULED && (
                                    <TableRow>
                                        <TableCell colSpan={4}>
                                            <form onSubmit={handleRegister}>
                                                <div className="flex flex-col items-center gap-4 md:flex-row">
                                                    <Select onValueChange={setSelectedPlayerId}>
                                                        <SelectTrigger className="w-full md:w-64">
                                                            <SelectValue placeholder="Select player" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {eligiblePlayers.length > 0 ? (
                                                                eligiblePlayers.map((player) => (
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
                                                                                {player.name} — Age: {calculateAge(player.dob)}, Weight:{' '}
                                                                                {player.weight} Kg
                                                                            </span>
                                                                        </div>
                                                                    </SelectItem>
                                                                ))
                                                            ) : (
                                                                <div className="text-muted-foreground px-3 py-2 text-sm">
                                                                    All players are registered
                                                                </div>
                                                            )}
                                                        </SelectContent>
                                                    </Select>

                                                    <Select onValueChange={setSelectedClubId}>
                                                        <SelectTrigger className="w-full md:w-64">
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

                                                    <Button type="submit" disabled={!selectedPlayerId || !selectedClubId}>
                                                        Register
                                                    </Button>
                                                </div>
                                            </form>
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
